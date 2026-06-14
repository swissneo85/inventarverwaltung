<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Box;
use App\Models\Room;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    /**
     * Dashboard-Statistik
     */
    public function stats(Request $request)
    {
        $stats = [
            'items' => [
                'total' => Item::count(),
                'in_boxes' => Item::whereNotNull('box_id')->count(),
                'in_rooms' => Item::whereNotNull('room_id')->whereNull('box_id')->count(),
                'in_inbox' => Item::where('is_in_inbox', true)->count(),
            ],
            'boxes' => [
                'total' => Box::count(),
                'in_rooms' => Box::whereNotNull('room_id')->count(),
                'in_inbox' => Box::where('is_in_inbox', true)->count(),
            ],
            'rooms' => Room::where('active', true)->count(),
            'categories' => Category::where('active', true)->count(),
        ];

        // Garantie läuft bald ab
        $warrantyExpiring = Item::warrantyExpiring(30)->count();
        $stats['alerts'] = [
            'warranty_expiring' => $warrantyExpiring,
        ];

        return $this->success($stats);
    }

    /**
     * Letzte Items
     */
    public function recentItems(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $items = Item::with(['category', 'room', 'box'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($items);
    }

    /**
     * Inbox-Übersicht
     */
    public function inbox(Request $request)
    {
        $items = Item::with(['category', 'coverImage'])
            ->where('is_in_inbox', true)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $boxes = Box::with(['room', 'coverImage'])
            ->where('is_in_inbox', true)
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $this->success([
            'items' => $items,
            'boxes' => $boxes,
            'items_count' => Item::where('is_in_inbox', true)->count(),
            'boxes_count' => Box::where('is_in_inbox', true)->count(),
        ]);
    }

    /**
     * Garantie-Übersicht
     */
    public function warranties(Request $request)
    {
        $days = $request->get('days', 90);

        $expiring = Item::with(['category', 'room', 'box'])
            ->whereNotNull('warranty_until')
            ->where('warranty_until', '<=', now()->addDays($days))
            ->where('warranty_until', '>=', now())
            ->orderBy('warranty_until')
            ->get();

        $expired = Item::with(['category', 'room', 'box'])
            ->whereNotNull('warranty_until')
            ->where('warranty_until', '<', now())
            ->orderBy('warranty_until')
            ->get();

        return $this->success([
            'expiring' => $expiring,
            'expired' => $expired,
        ]);
    }

    /**
     * Kategorie-Verteilung
     */
    public function categoryDistribution(Request $request)
    {
        $distribution = Category::withCount('items')
            ->having('items_count', '>', 0)
            ->orderBy('items_count', 'desc')
            ->get();

        return $this->success($distribution);
    }

    /**
     * Raum-Verteilung
     */
    public function roomDistribution(Request $request)
    {
        $distribution = Room::withCount(['items', 'boxes'])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return $this->success($distribution);
    }
}