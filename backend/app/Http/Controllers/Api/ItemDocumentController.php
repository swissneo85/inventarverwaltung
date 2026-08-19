<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\ItemDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemDocumentController extends BaseApiController
{
    public function index($itemId)
    {
        $item = Item::findOrFail($itemId);
        return $this->success($item->documents()->get());
    }

    public function store(Request $request, $itemId)
    {
        $item = Item::findOrFail($itemId);

        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,gif,webp',
            'type' => 'required|in:quittung,anleitung,garantie,foto,sonstiges',
        ]);

        $file = $request->file('file');

        // Endung aus dem tatsächlichen Dateiinhalt ableiten (nicht vom Client-Dateinamen),
        // damit eine hochgeladene Datei nicht unter einer vom Client vorgegebenen,
        // potentiell ausführbaren Endung (z. B. .php) gespeichert werden kann.
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower((string) $file->guessExtension());
        if (!in_array($ext, $allowedExtensions, true)) {
            return $this->error('Ungültiger Dateityp', 422);
        }

        $uuid = Str::uuid()->toString();
        $path = 'documents/items/' . $itemId . '/' . $uuid . '.' . $ext;

        Storage::disk('public')->putFileAs(
            'documents/items/' . $itemId,
            $file,
            $uuid . '.' . $ext
        );

        $document = ItemDocument::create([
            'item_id'   => $itemId,
            'type'      => $request->input('type'),
            'name'      => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return $this->success($document, 'Dokument hochgeladen', 201);
    }

    public function destroy($itemId, $documentId)
    {
        $document = ItemDocument::where('item_id', $itemId)->findOrFail($documentId);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return $this->success(null, 'Dokument gelöscht');
    }
}
