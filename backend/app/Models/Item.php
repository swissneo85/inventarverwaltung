<?php

namespace App\Models;

use App\Models\Concerns\DeletesMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Item extends Model
{
    use HasFactory, DeletesMedia;

    const STATUS_VALUES = ['aktiv', 'entsorgt', 'verkauft', 'verschenkt', 'verloren', 'defekt_entsorgt'];

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'subcategory_id',
        'person_id',
        'loaned_to_person_id',
        'brand',
        'model',
        'serial_number',
        'article_number',
        'ean',
        'inventory_number',
        'room_id',
        'box_id',
        'is_in_inbox',
        'quantity',
        'unit',
        'condition',
        'status',
        'status_datum',
        'status_notiz',
        'notes',
        'purchased_at',
        'warranty_until',
        'purchase_price',
        'currency',
        'purchase_location',
        'dealer',
        'order_number',
        'invoice_present',
        'invoice_file',
        'image',
        'qr_token',
    ];

    protected $attributes = [
        'status' => 'aktiv',
    ];

    protected $casts = [
        'is_in_inbox' => 'boolean',
        'invoice_present' => 'boolean',
        'quantity' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'purchased_at' => 'date',
        'warranty_until' => 'date',
        'status_datum' => 'date',
        'room_id' => 'integer',
        'box_id' => 'integer',
        'category_id' => 'integer',
        'subcategory_id' => 'integer',
        'person_id' => 'integer',
        'loaned_to_person_id' => 'integer',
    ];

    protected $appends = ['display_id', 'qr_code_url', 'location_type', 'image_url'];

    /**
     * Sichtbare Kennung: I{id}
     */
    public function getDisplayIdAttribute(): string
    {
        return 'I' . $this->id;
    }

    /**
     * QR-Code URL für dieses Item
     */
    public function getQrCodeUrlAttribute(): string
    {
        return config('app.url') . '/scan/' . $this->qr_token;
    }

    /**
     * Standort-Typ: inbox, room, box
     */
    public function getLocationTypeAttribute(): string
    {
        if ($this->is_in_inbox) {
            return 'inbox';
        }
        if ($this->box_id) {
            return 'box';
        }
        if ($this->room_id) {
            return 'room';
        }
        return 'unknown';
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('coverImage') && $this->coverImage) {
            return $this->coverImage->url;
        }
        return null;
    }

    /**
     * Ort des Items (Inbox, Raum oder Box)
     */
    public function getLocationNameAttribute(): string
    {
        if ($this->is_in_inbox) {
            return 'Inbox';
        }
        if ($this->box_id && $this->box) {
            return 'Box: ' . $this->box->name . ' (B' . $this->box->id . ')';
        }
        if ($this->room_id && $this->room) {
            return 'Raum: ' . $this->room->name . ' (R' . $this->room->id . ')';
        }
        return 'Unbekannt';
    }

    /**
     * Kategorie
     */
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function loanedToPerson()
    {
        return $this->belongsTo(Person::class, 'loaned_to_person_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Unterkategorie (falls vorhanden)
     */
    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * Raum (wenn direkt im Raum)
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Box (wenn in einer Box)
     */
    public function box()
    {
        return $this->belongsTo(Box::class);
    }

    public function images()
    {
        return $this->morphMany(\App\Models\ItemImage::class, 'imageable')->orderBy('sort_order');
    }

    public function documents()
    {
        return $this->hasMany(ItemDocument::class)->orderBy('type');
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
     * Suche nach Display-ID (I12 -> id=12)
     */
    public static function findByDisplayId(string $displayId): ?self
    {
        if (preg_match('/^I(\d+)$/i', $displayId, $matches)) {
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
     * Zu Raum zuweisen
     */
    public function assignToRoom(Room $room): void
    {
        $this->room_id = $room->id;
        $this->box_id = null;
        $this->is_in_inbox = false;
        $this->save();
    }

    /**
     * Zu Box zuweisen
     */
    public function assignToBox(Box $box): void
    {
        $this->box_id = $box->id;
        $this->room_id = null;
        $this->is_in_inbox = false;
        $this->save();
    }

    /**
     * Zur Inbox verschieben
     */
    public function moveToInbox(): void
    {
        $this->room_id = null;
        $this->box_id = null;
        $this->is_in_inbox = true;
        $this->save();
    }

    /**
     * Scopes
     */
    public function scopeAktiv($query)
    {
        return $query->where('status', 'aktiv');
    }

    public function scopeArchiviert($query)
    {
        return $query->where('status', '!=', 'aktiv');
    }

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

    public function scopeInBox($query, $boxId)
    {
        return $query->where('box_id', $boxId);
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeWarrantyExpiring($query, $days = 30)
    {
        return $query->whereNotNull('warranty_until')
            ->where('warranty_until', '<=', now()->addDays($days))
            ->where('warranty_until', '>=', now());
    }

    /**
     * Suche (Name, Beschreibung, Nummern)
     */
    public function scopeSearch($query, string $term)
    {
        $term = '%' . $term . '%';
        
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhere('serial_number', 'like', $term)
                ->orWhere('article_number', 'like', $term)
                ->orWhere('ean', 'like', $term)
                ->orWhere('inventory_number', 'like', $term)
                ->orWhere('brand', 'like', $term)
                ->orWhere('model', 'like', $term)
                ->orWhere('purchase_location', 'like', $term);
        });
    }
}