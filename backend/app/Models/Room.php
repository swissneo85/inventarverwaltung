<?php

namespace App\Models;

use App\Models\Concerns\DeletesMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory, DeletesMedia;

    protected $fillable = [
        'name',
        'description',
        'image',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['display_id', 'image_url'];

    /**
     * Sichtbare Kennung: R{id}
     */
    public function getDisplayIdAttribute(): string
    {
        return 'R' . $this->id;
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('coverImage') && $this->coverImage) {
            return $this->coverImage->url;
        }
        return null;
    }

    public function images()
    {
        return $this->morphMany(\App\Models\ItemImage::class, 'imageable')->orderBy('sort_order');
    }

    public function coverImage()
    {
        return $this->morphOne(\App\Models\ItemImage::class, 'imageable')->orderBy('sort_order');
    }

    /**
     * Items direkt in diesem Raum
     */
    public function items()
    {
        return $this->hasMany(Item::class)->whereNull('box_id')->where('is_in_inbox', false);
    }

    /**
     * Alle Items in diesem Raum (inkl. in Boxen)
     */
    public function allItems()
    {
        return $this->hasMany(Item::class)->where('is_in_inbox', false);
    }

    /**
     * Boxen in diesem Raum
     */
    public function boxes()
    {
        return $this->hasMany(Box::class)->where('is_in_inbox', false);
    }

    /**
     * Suche nach Display-ID (R12 -> id=12)
     */
    public static function findByDisplayId(string $displayId): ?self
    {
        if (preg_match('/^R(\d+)$/i', $displayId, $matches)) {
            return self::find($matches[1]);
        }
        return null;
    }

    /**
     * Aktiv-Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}