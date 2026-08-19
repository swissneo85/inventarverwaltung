<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'parent_id' => 'integer',
    ];

    /**
     * Hauptkategorie (falls Unterkategorie)
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Unterkategorien
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Items in dieser Kategorie
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'category_id');
    }

    public function viewerPermissions()
    {
        return $this->belongsToMany(User::class, 'user_category_permissions');
    }

    /**
     * Ist dies eine Unterkategorie?
     */
    public function isSubcategory(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Ist dies eine Hauptkategorie?
     */
    public function isMainCategory(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Alle Items (inkl. Unterkategorien)
     */
    public function allItems()
    {
        $categoryIds = $this->getAllChildIds();
        $categoryIds[] = $this->id;
        
        return Item::whereIn('category_id', $categoryIds);
    }

    /**
     * Alle Kind-IDs rekursiv holen
     */
    public function getAllChildIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllChildIds());
        }
        return $ids;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeMainCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }
}