<?php

namespace App\Models\Concerns;

trait DeletesMedia
{
    public static function bootDeletesMedia(): void
    {
        static::deleting(function ($model) {
            // Each ItemImage's own deleting event handles physical file deletion
            $model->images()->each(fn ($img) => $img->delete());

            // Only Item has documents; guard prevents call on Box/Room
            if (method_exists($model, 'documents')) {
                $model->documents()->each(fn ($doc) => $doc->delete());
            }
        });
    }
}
