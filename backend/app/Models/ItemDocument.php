<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ItemDocument extends Model
{
    protected $fillable = ['item_id', 'type', 'name', 'file_path', 'mime_type', 'file_size'];

    protected static function booted(): void
    {
        static::deleting(function (ItemDocument $doc) {
            Storage::disk('public')->delete($doc->file_path);
        });
    }

    const TYPES = [
        'quittung'  => 'Quittung',
        'anleitung' => 'Bedienungsanleitung',
        'garantie'  => 'Garantieschein',
        'foto'      => 'Foto (Typenschild/SN)',
        'sonstiges' => 'Sonstiges',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
