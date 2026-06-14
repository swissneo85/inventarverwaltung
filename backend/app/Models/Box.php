<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Box extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'room_id',
        'is_in_inbox',
        'location_detail',
        'box_type',
        'qr_token',
        'nfc_code',
        'image',
        'sort_order',
    ];

    protected $casts = [
        'is_in_inbox' => 'boolean',
        'sort_order' => 'integer',
        'room_id' => 'integer',
    ];

    protected $appends = ['display_id', 'qr_code_url', 'image_url'];

    /**
     * Sichtbare Kennung: B{id}
     */
    public function getDisplayIdAttribute(): string
    {
        return 'B' . $this->id;
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('coverImage') && $this->coverImage) {
            return $this->coverImage->url;
        }
        return null;
    }

    /**
     * QR-Code URL für diese Box
     */
    public function getQrCodeUrlAttribute(): string
    {
        return config('app.url') . '/scan/' . $this->qr_token;
    }

    /**
     * Raum, in dem diese Box steht
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Items in dieser Box
     */
    public function items()
    {
        return $this->hasMany(Item::class);
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
     * QR-Code generieren
     */
    public function generateQrCode(): string
    {
        $this->qr_token = \Str::random(32);
        $this->save();
        return $this->qr_token;
    }

    /**
     * QR-Code als Bild (Base64)
     */
    public function getQrCodeImageBase64(): string
    {
        if (!$this->qr_token) {
            $this->generateQrCode();
        }
        
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($this->qr_code_url);
        
        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    /**
     * Suche nach Display-ID (B12 -> id=12)
     */
    public static function findByDisplayId(string $displayId): ?self
    {
        if (preg_match('/^B(\d+)$/i', $displayId, $matches)) {
            return self::find($matches[1]);
        }
        return null;
    }

    /**
     * Suche nach QR-Token
     */
    public static function findByQrToken(string $token): ?self
    {
        return self::where('qr_token', $token)->first();
    }

    /**
     * Scopes
     */
    public function scopeInInbox($query)
    {
        return $query->where('is_in_inbox', true);
    }

    public function scopeNotInInbox($query)
    {
        return $query->where('is_in_inbox', false);
    }

    public function scopeInRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}