<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Bestehende Items auf 'aktiv' setzen (inkl. NULL und alte Werte wie 'Verfügbar')
        $validValues = ['aktiv', 'entsorgt', 'verkauft', 'verschenkt', 'verloren', 'defekt_entsorgt'];
        DB::table('items')
            ->whereNull('status')
            ->orWhereNotIn('status', $validValues)
            ->update(['status' => 'aktiv']);

        Schema::table('items', function (Blueprint $table) {
            $table->date('status_datum')->nullable()->after('status');
            $table->text('status_notiz')->nullable()->after('status_datum');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['status_datum', 'status_notiz']);
        });
    }
};
