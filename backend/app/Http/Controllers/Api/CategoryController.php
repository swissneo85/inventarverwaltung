<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    /**
     * Alle Kategorien auflisten
     */
    public function index(Request $request)
    {
        $query = Category::with('children')->withCount('items');
        
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->has('parents_only')) {
            $query->mainCategories();
        }
        
        $categories = $query->ordered()->get();
        
        // Hierarchische Struktur aufbauen
        if ($request->has('tree')) {
            $categories = $this->buildTree($categories);
        }

        return $this->success($categories);
    }

    /**
     * Baumstruktur aufbauen
     */
    private function buildTree($categories, $parentId = null)
    {
        $tree = [];
        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $category->children = $this->buildTree($categories, $category->id);
                $tree[] = $category;
            }
        }
        return $tree;
    }

    /**
     * Kategorie erstellen
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = Category::create($request->only([
            'name', 'description', 'parent_id'
        ]));

        return $this->success($category, 'Kategorie erstellt', 201);
    }

    /**
     * Kategorie anzeigen
     */
    public function show(Request $request, $id)
    {
        $category = Category::with(['parent', 'children.parent'])->find($id);
        
        if (!$category) {
            return $this->error('Kategorie nicht gefunden', 404);
        }

        $category->items_count = $category->items()->count();
        $category->all_items_count = $category->allItems()->count();

        return $this->success($category);
    }

    /**
     * Kategorie bearbeiten
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return $this->error('Kategorie nicht gefunden', 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'active' => 'nullable|boolean',
        ]);

        // Verhindern dass Kategorie sich selbst als Parent hat
        if ($request->parent_id == $id) {
            return $this->error('Kategorie kann nicht sich selbst als Parent haben', 400);
        }

        $category->update($request->only([
            'name', 'description', 'parent_id', 'active'
        ]));

        return $this->success($category, 'Kategorie aktualisiert');
    }

    /**
     * Kategorie löschen
     */
    public function destroy(Request $request, $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return $this->error('Kategorie nicht gefunden', 404);
        }

        // Prüfen ob Items in dieser Kategorie sind
        $itemsCount = $category->items()->count();
        if ($itemsCount > 0) {
            return $this->error("Kategorie kann nicht gelöscht werden. Enthält {$itemsCount} Items.", 400);
        }

        // Unterkategorien auf Hauptkategorie setzen oder löschen
        $category->children()->update(['parent_id' => $category->parent_id]);
        $category->delete();

        return $this->success(null, 'Kategorie gelöscht');
    }

    /**
     * Items in dieser Kategorie
     */
    public function items(Request $request, $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return $this->error('Kategorie nicht gefunden', 404);
        }

        $query = $category->items()->with(['room', 'box']);
        
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $items = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return $this->success($items);
    }

    /**
     * Benutzer mit Zugriff auf diese Kategorie
     */
    public function users(Request $request, $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return $this->error('Kategorie nicht gefunden', 404);
        }

        $users = $category->users()->get(['id', 'name', 'username', 'role']);

        return $this->success($users);
    }
}