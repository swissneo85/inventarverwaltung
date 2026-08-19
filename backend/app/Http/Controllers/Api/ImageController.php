<?php

namespace App\Http\Controllers\Api;

use App\Models\Box;
use App\Models\Item;
use App\Models\ItemImage;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImageController extends BaseApiController
{
    public function index(string $type, int|string $id)
    {
        $model = $this->resolveModel($type, $id);
        if (!$model) {
            return $this->error('Nicht gefunden', 404);
        }

        return $this->success($model->images()->get());
    }

    public function store(Request $r, string $type, int|string $id)
    {
        $model = $this->resolveModel($type, $id);
        if (!$model) {
            return $this->error('Nicht gefunden', 404);
        }

        $r->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:15360',
        ]);

        $file = $r->file('image');

        // Endung aus dem tatsächlichen Dateiinhalt ableiten (nicht vom Client-Dateinamen),
        // damit eine hochgeladene Datei nicht unter einer vom Client vorgegebenen,
        // potentiell ausführbaren Endung (z. B. .php) gespeichert werden kann.
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'];
        $ext = strtolower((string) $file->guessExtension());
        if (!in_array($ext, $allowedExtensions, true)) {
            return $this->error('Ungültiger Dateityp', 422);
        }

        $uuid = Str::uuid()->toString();
        $path = 'images/' . $uuid . '.' . $ext;

        Storage::disk('public')->putFileAs('images', $file, $uuid . '.' . $ext);

        $maxOrder = $model->images()->max('sort_order') ?? -1;

        $image = $model->images()->create([
            'imageable_type' => get_class($model),
            'imageable_id'   => $model->id,
            'filename'       => $file->getClientOriginalName(),
            'path'           => $path,
            'mime_type'      => $file->getMimeType(),
            'size'           => $file->getSize(),
            'sort_order'     => $maxOrder + 1,
        ]);

        return $this->success($image, 'Bild hochgeladen', 201);
    }

    public function destroy(int|string $imageId)
    {
        $image = ItemImage::find($imageId);
        if (!$image) {
            return $this->error('Nicht gefunden', 404);
        }

        $image->delete();

        return $this->success(null, 'Bild gelöscht');
    }

    public function reorder(Request $r, string $type, int|string $id)
    {
        $model = $this->resolveModel($type, $id);
        if (!$model) {
            return $this->error('Nicht gefunden', 404);
        }

        $r->validate([
            'order'   => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($r->input('order') as $sortOrder => $imageId) {
            $model->images()->where('id', $imageId)->update(['sort_order' => $sortOrder]);
        }

        return $this->success($model->images()->get(), 'Reihenfolge aktualisiert');
    }

    private function resolveModel(string $type, int|string $id): Item|Box|Room|null
    {
        return match ($type) {
            'items' => Item::find($id),
            'boxes' => Box::find($id),
            'rooms' => Room::find($id),
            default => null,
        };
    }
}
