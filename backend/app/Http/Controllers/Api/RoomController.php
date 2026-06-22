<?php

namespace App\Http\Controllers\Api;

use App\Models\Room;
use App\Models\Item;
use App\Models\Box;
use Illuminate\Http\Request;

class RoomController extends BaseApiController
{
    /**
     * Alle Räume auflisten
     */
    public function index(Request $request)
    {
        $query = Room::with(['coverImage'])->withCount(['items', 'boxes']);
        
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }
        
        $rooms = $query->ordered()->get();
        
        return $this->success($rooms);
    }

    /**
     * Raum erstellen
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $room = Room::create($request->only([
            'name', 'description', 'image', 'sort_order'
        ]));

        return $this->success($room, 'Raum erstellt', 201);
    }

    /**
     * Raum anzeigen
     */
    public function show(Request $request, $id)
    {
        $room = Room::findByDisplayId($id) ?? Room::find($id);
        
        if (!$room) {
            return $this->error('Raum nicht gefunden', 404);
        }

        // Prüfen ob Benutzer Zugriff hat
        if (!$request->user()->isAdmin()) {
            // Regular users can see rooms, but items will be filtered elsewhere
        }

        $room->load(['boxes', 'items']);
        $room->items_count = $room->items()->count();
        $room->boxes_count = $room->boxes()->count();
        $room->all_items_count = $room->allItems()->count();

        return $this->success($room);
    }

    /**
     * Raum bearbeiten
     */
    public function update(Request $request, $id)
    {
        $room = Room::find($id);
        
        if (!$room) {
            return $this->error('Raum nicht gefunden', 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'active' => 'nullable|boolean',
        ]);

        $room->update($request->only([
            'name', 'description', 'image', 'sort_order', 'active'
        ]));

        return $this->success($room, 'Raum aktualisiert');
    }

    /**
     * Raum löschen
     */
    public function destroy(Request $request, $id)
    {
        $room = Room::find($id);
        
        if (!$room) {
            return $this->error('Raum nicht gefunden', 404);
        }

        // Prüfen ob Items oder Boxen im Raum sind
        $itemsCount = $room->items()->count();
        $boxesCount = $room->boxes()->count();
        
        if ($itemsCount > 0 || $boxesCount > 0) {
            return $this->error("Raum kann nicht gelöscht werden. Enthält {$itemsCount} Items und {$boxesCount} Boxen.", 400);
        }

        $room->delete();

        return $this->success(null, 'Raum gelöscht');
    }

    /**
     * Items in einem Raum
     */
    public function items(Request $request, $id)
    {
        $room = Room::find($id);
        
        if (!$room) {
            return $this->error('Raum nicht gefunden', 404);
        }

        $query = $room->items()->with(['category', 'coverImage', 'room', 'box.room', 'parentItem.room', 'parentItem.box.room']);

        // Status-Filter (Default: nur aktive Items)
        $statusFilter = $request->get('status', 'aktiv');
        if ($statusFilter === 'archiviert') {
            $query->where('status', '!=', 'aktiv');
        } elseif ($statusFilter !== 'alle') {
            $query->where('status', $statusFilter);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $items = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return $this->success($items);
    }

    /**
     * Boxen in einem Raum
     */
    public function boxes(Request $request, $id)
    {
        $room = Room::find($id);
        
        if (!$room) {
            return $this->error('Raum nicht gefunden', 404);
        }

        $boxes = $room->boxes()->with(['coverImage'])->withCount('items')->ordered()->paginate($request->get('per_page', 50));

        return $this->success($boxes);
    }
}