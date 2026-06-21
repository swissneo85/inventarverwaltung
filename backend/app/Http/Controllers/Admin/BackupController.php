<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Box;
use App\Models\Item;
use App\Models\Room;
use Carbon\Carbon;
use FilesystemIterator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class BackupController extends BaseApiController
{
    private function dbPath(): string
    {
        return config('database.connections.' . config('database.default') . '.database');
    }

    private function storagePubPath(): string
    {
        return rtrim(Storage::disk('public')->path(''), '/');
    }

    private function appVersion(): string
    {
        return env('APP_VERSION', 'dev');
    }

    private function buildZip(string $zipPath): void
    {
        $tempDir = storage_path('app/_backup_tmp_' . uniqid());
        mkdir($tempDir, 0755, true);

        try {
            $snapshotPath = $tempDir . '/database.sqlite';
            DB::statement("VACUUM INTO '$snapshotPath'");

            $manifest = [
                'app_version'     => $this->appVersion(),
                'created_at'      => Carbon::now()->toIso8601String(),
                'counts'          => [
                    'rooms' => Room::count(),
                    'boxes' => Box::count(),
                    'items' => Item::count(),
                ],
                'php_version'     => PHP_VERSION,
                'laravel_version' => app()->version(),
            ];
            file_put_contents($tempDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('ZIP-Datei konnte nicht erstellt werden');
            }

            $zip->addFile($snapshotPath, 'database.sqlite');
            $zip->addFile($tempDir . '/manifest.json', 'manifest.json');

            $pub = $this->storagePubPath();
            if (is_dir($pub)) {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($pub, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($it as $file) {
                    if ($file->isFile()) {
                        $rel = 'storage/' . ltrim(substr($file->getPathname(), strlen($pub)), '/');
                        $zip->addFile($file->getPathname(), $rel);
                    }
                }
            }

            $zip->close();
        } finally {
            $this->rmdirRecursive($tempDir);
        }
    }

    public function create(Request $request)
    {
        $ts      = Carbon::now()->format('Y-m-d_H-i-s');
        $name    = "backup_{$ts}.zip";
        $zipPath = storage_path("app/_dl_{$ts}.zip");

        try {
            $this->buildZip($zipPath);

            return response()
                ->download($zipPath, $name, ['Content-Type' => 'application/zip'])
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }
            return $this->error('Backup-Erstellung fehlgeschlagen: ' . $e->getMessage(), 500);
        }
    }

    public function preview(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:zip']);

        $tmpPath = $request->file('file')->getPathname();

        try {
            $zip = new ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                return $this->error('Ungültige ZIP-Datei', 422);
            }
            $raw = $zip->getFromName('manifest.json');
            $zip->close();

            if ($raw === false) {
                return $this->error('Kein gültiges Backup: manifest.json fehlt', 422);
            }

            $manifest = json_decode($raw, true);
            if (!is_array($manifest)) {
                return $this->error('manifest.json ist beschädigt', 422);
            }

            return $this->success([
                'manifest'        => $manifest,
                'current_version' => $this->appVersion(),
                'version_match'   => ($manifest['app_version'] ?? '') === $this->appVersion(),
            ]);
        } catch (\Throwable $e) {
            return $this->error('Fehler beim Lesen der Backup-Datei: ' . $e->getMessage(), 422);
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'file'    => 'required|file|mimes:zip|max:204800',
            'confirm' => 'required|string',
        ]);

        if (trim($request->input('confirm')) !== 'WIEDERHERSTELLEN') {
            return $this->error('Bestätigung ungültig: Bitte genau "WIEDERHERSTELLEN" eingeben', 422);
        }

        // Save uploaded ZIP to known temp path
        $tmpZip = storage_path('app/_restore_' . uniqid() . '.zip');
        copy($request->file('file')->getPathname(), $tmpZip);

        // Validate ZIP structure
        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            return $this->error('Ungültige ZIP-Datei', 422);
        }

        $hasManifest = $zip->getFromName('manifest.json') !== false;
        $sqliteEntry = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with($name, '.sqlite')) {
                $sqliteEntry = $name;
                break;
            }
        }
        $zip->close();

        if (!$hasManifest || $sqliteEntry === null) {
            @unlink($tmpZip);
            return $this->error('Ungültiges Backup: manifest.json oder .sqlite-Datei fehlt', 422);
        }

        // Pre-restore backup
        $ts         = Carbon::now()->format('Y-m-d_H-i-s');
        $backupDir  = storage_path('app/backups');
        @mkdir($backupDir, 0755, true);
        $preZip     = $backupDir . "/pre-restore_{$ts}.zip";

        try {
            $this->buildZip($preZip);
        } catch (\Throwable $e) {
            @unlink($tmpZip);
            return $this->error('Pre-Restore-Backup fehlgeschlagen: ' . $e->getMessage(), 500);
        }

        Artisan::call('down');

        $dbPath  = $this->dbPath();
        $pubPath = $this->storagePubPath();

        try {
            $zip = new ZipArchive();
            $zip->open($tmpZip);

            // Extract DB to temp, then move into place
            $tmpExtract = storage_path('app/_restore_extract_' . uniqid());
            mkdir($tmpExtract, 0755, true);
            $zip->extractTo($tmpExtract, [$sqliteEntry]);
            copy($tmpExtract . '/' . $sqliteEntry, $dbPath);
            chmod($dbPath, 0666);
            $this->rmdirRecursive($tmpExtract);

            // Replace storage
            $this->clearDir($pubPath);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'storage/') && !str_ends_with($name, '/')) {
                    $rel     = substr($name, strlen('storage/'));
                    $dest    = $pubPath . '/' . $rel;
                    $destDir = dirname($dest);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    file_put_contents($dest, $zip->getFromName($name));
                }
            }

            $zip->close();
            @unlink($tmpZip);

            // Reconnect to restored DB, then migrate to bring schema up to date
            DB::disconnect();
            $migrateCode = Artisan::call('migrate', ['--force' => true]);
            if ($migrateCode !== 0) {
                Artisan::call('up');
                return $this->error(
                    'Datenbank und Dateien wurden wiederhergestellt, aber die Schema-Migration ' .
                    'schlug fehl. Bitte manuell prüfen oder Pre-Restore-Backup verwenden: ' .
                    basename($preZip),
                    500
                );
            }

            Artisan::call('up');
            return $this->success(null, 'Wiederherstellung erfolgreich abgeschlossen');
        } catch (\Throwable $e) {
            try {
                $zip->close();
            } catch (\Throwable $ignored) {}

            // Rollback
            try {
                $rb = new ZipArchive();
                if ($rb->open($preZip) === true) {
                    $tmpRb = storage_path('app/_rollback_' . uniqid());
                    mkdir($tmpRb, 0755, true);
                    for ($i = 0; $i < $rb->numFiles; $i++) {
                        $name = $rb->getNameIndex($i);
                        if (str_ends_with($name, '.sqlite')) {
                            $rb->extractTo($tmpRb, [$name]);
                            copy($tmpRb . '/' . $name, $dbPath);
                            chmod($dbPath, 0666);
                            break;
                        }
                    }
                    $this->clearDir($pubPath);
                    for ($i = 0; $i < $rb->numFiles; $i++) {
                        $name = $rb->getNameIndex($i);
                        if (str_starts_with($name, 'storage/') && !str_ends_with($name, '/')) {
                            $rel     = substr($name, strlen('storage/'));
                            $dest    = $pubPath . '/' . $rel;
                            $destDir = dirname($dest);
                            if (!is_dir($destDir)) {
                                mkdir($destDir, 0755, true);
                            }
                            file_put_contents($dest, $rb->getFromName($name));
                        }
                    }
                    $rb->close();
                    $this->rmdirRecursive($tmpRb);
                }
            } catch (\Throwable $ignored) {}

            @unlink($tmpZip);
            Artisan::call('up');

            return $this->error(
                'Wiederherstellung fehlgeschlagen: ' . $e->getMessage() .
                ' — System wurde auf vorherigen Stand zurückgesetzt.',
                500
            );
        }
    }

    private function clearDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
    }

    private function rmdirRecursive(string $dir): void
    {
        $this->clearDir($dir);
        if (is_dir($dir)) {
            @rmdir($dir);
        }
    }
}
