<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Box;
use App\Models\Room;
use App\Models\Category;
use Illuminate\Http\Request;

class SearchController extends BaseApiController
{
    /**
     * Globale Suche
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:255',
            'type' => 'nullable|in:all,items,boxes,rooms,categories',
        ]);

        $term = $request->q;
        $type = $request->get('type', 'all');
        $results = [];

        // Display-ID erkannt? (R1, B12, I500)
        if (preg_match('/^([RBI])(\d+)$/i', $term, $matches)) {
            return $this->findByDisplayId($matches[1], $matches[2]);
        }

        // Items suchen (nur aktive Items)
        if ($type === 'all' || $type === 'items') {
            $items = Item::with([
                    'category', 'room', 'box.room',
                    'parentItem.room', 'parentItem.box.room',
                    'parentItem.parentItem.room', 'parentItem.parentItem.box.room',
                ])
                ->aktiv()
                ->search($term)
                ->orderBy('name')
                ->limit(20)
                ->get();

            $results['items'] = $items;
        }

        // Boxen suchen
        if ($type === 'all' || $type === 'boxes') {
            $boxes = Box::with(['room'])
                ->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->map(function ($box) {
                    $box->items_count = $box->items()->count();
                    return $box;
                });
            
            $results['boxes'] = $boxes;
        }

        // Räume suchen
        if ($type === 'all' || $type === 'rooms') {
            $rooms = Room::where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->map(function ($room) {
                    $room->items_count = $room->items()->count();
                    $room->boxes_count = $room->boxes()->count();
                    return $room;
                });
            
            $results['rooms'] = $rooms;
        }

        // Kategorien suchen
        if ($type === 'all' || $type === 'categories') {
            $categories = Category::where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->map(function ($cat) {
                    $cat->items_count = $cat->items()->count();
                    return $cat;
                });
            
            $results['categories'] = $categories;
        }

        return $this->success($results);
    }

    /**
     * Nach Display-ID suchen
     */
    private function findByDisplayId($type, $id)
    {
        switch (strtoupper($type)) {
            case 'I':
                $item = Item::with([
                    'category', 'room', 'box.room',
                    'parentItem.room', 'parentItem.box.room',
                ])->find($id);
                if ($item) {
                    return $this->success([
                        'type' => 'item',
                        'data' => $item,
                    ]);
                }
                break;
            
            case 'B':
                $box = Box::with(['room'])->find($id);
                if ($box) {
                    $box->items_count = $box->items()->count();
                    return $this->success([
                        'type' => 'box',
                        'data' => $box,
                    ]);
                }
                break;
            
            case 'R':
                $room = Room::find($id);
                if ($room) {
                    $room->items_count = $room->items()->count();
                    $room->boxes_count = $room->boxes()->count();
                    return $this->success([
                        'type' => 'room',
                        'data' => $room,
                    ]);
                }
                break;
        }

        return $this->error('Nicht gefunden', 404);
    }

    /**
     * Schnellsuche für Autovervollständigung
     */
    public function quick(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $term = $request->q;
        $results = [];

        // Items (nur aktive, Name, Beschreibung, Seriennummer, Modell, Hersteller)
        $items = Item::with([
                'room', 'box.room',
                'parentItem.room', 'parentItem.box.room',
                'parentItem.parentItem.room', 'parentItem.parentItem.box.room',
            ])
            ->aktiv()
            ->search($term)
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'item',
                    'id' => $item->id,
                    'display_id' => 'I' . $item->id,
                    'name' => $item->name,
                    'location_path' => $item->location_path,
                    'parent_item_id' => $item->parent_item_id,
                ];
            });

        // Boxen (Name + Beschreibung)
        $boxes = Box::where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            })
            ->limit(5)
            ->get(['id', 'name'])
            ->map(function ($box) {
                return [
                    'type' => 'box',
                    'id' => $box->id,
                    'display_id' => 'B' . $box->id,
                    'name' => $box->name,
                ];
            });

        // Räume (Name + Beschreibung)
        $rooms = Room::where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            })
            ->limit(5)
            ->get(['id', 'name'])
            ->map(function ($room) {
                return [
                    'type' => 'room',
                    'id' => $room->id,
                    'display_id' => 'R' . $room->id,
                    'name' => $room->name,
                ];
            });

        // Display-ID Treffer
        if (preg_match('/^([RBI])(\d*)$/i', $term)) {
            $results['display_id_match'] = true;
        }

        $results['items'] = $items;
        $results['boxes'] = $boxes;
        $results['rooms'] = $rooms;

        return $this->success($results);
    }
}