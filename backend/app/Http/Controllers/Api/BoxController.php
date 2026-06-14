<?php

namespace App\Http\Controllers\Api;

use App\Models\Box;
use App\Models\Room;
use Illuminate\Http\Request;

class BoxController extends BaseApiController
{
    /**
     * Alle Boxen auflisten
     */
    public function index(Request $request)
    {
        $query = Box::with(['room:id,name', 'coverImage'])->withCount('items');
        
        // Filter
        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }
        
        if ($request->has('in_inbox')) {
            $query->where('is_in_inbox', $request->boolean('in_inbox'));
        }

        if ($request->has('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }
        
        $boxes = $query->ordered()->paginate($request->get('per_page', 50));

        return $this->success($boxes);
    }

    /**
     * Inbox-Boxen auflisten
     */
    public function inbox(Request $request)
    {
        $boxes = Box::with(['room:id,name'])
            ->withCount('items')
            ->where('is_in_inbox', true)
            ->ordered()
            ->get();

        return $this->success($boxes);
    }

    /**
     * Box erstellen
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'room_id' => 'nullable|exists:rooms,id',
            'is_in_inbox' => 'nullable|boolean',
            'location_detail' => 'nullable|string|max:255',
            'box_type' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $box = Box::create($request->only([
            'name', 'description', 'room_id', 'is_in_inbox',
            'location_detail', 'box_type', 'image', 'sort_order'
        ]));

        // Wenn kein Raum zugeordnet, automatisch in Inbox
        if (!$request->room_id && !$request->is_in_inbox) {
            $box->is_in_inbox = true;
            $box->save();
        }

        return $this->success($box->load('room'), 'Box erstellt', 201);
    }

    /**
     * Box anzeigen
     */
    public function show(Request $request, $id)
    {
        $box = Box::findByDisplayId($id) ?? Box::find($id);
        
        if (!$box) {
            return $this->error('Box nicht gefunden', 404);
        }

        $box->load(['room']);
        $box->items_count = $box->items()->count();
        $box->qr_code_image = $box->qr_token ? $box->getQrCodeImageBase64() : null;

        return $this->success($box);
    }

    /**
     * Box bearbeiten
     */
    public function update(Request $request, $id)
    {
        $box = Box::find($id);
        
        if (!$box) {
            return $this->error('Box nicht gefunden', 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'room_id' => 'nullable|exists:rooms,id',
            'is_in_inbox' => 'nullable|boolean',
            'location_detail' => 'nullable|string|max:255',
            'box_type' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $box->update($request->only([
            'name', 'description', 'room_id', 'is_in_inbox',
            'location_detail', 'box_type', 'image', 'sort_order'
        ]));

        return $this->success($box->load('room'), 'Box aktualisiert');
    }

    /**
     * Box löschen
     */
    public function destroy(Request $request, $id)
    {
        $box = Box::find($id);
        
        if (!$box) {
            return $this->error('Box nicht gefunden', 404);
        }

        $itemsCount = $box->items()->count();
        
        if ($itemsCount > 0) {
            // Items in die Inbox verschieben
            $box->items()->update([
                'box_id' => null,
                'room_id' => null,
                'is_in_inbox' => true
            ]);
        }

        $box->delete();

        return $this->success(['moved_to_inbox' => $itemsCount], 'Box gelöscht');
    }

    /**
     * Box einem Raum zuweisen
     */
    public function assignToRoom(Request $request, $id)
    {
        $box = Box::find($id);
        
        if (!$box) {
            return $this->error('Box nicht gefunden', 404);
        }

        $request->validate([
            'room_id' => 'required|exists:rooms,id',
        ]);

        $box->room_id = $request->room_id;
        $box->is_in_inbox = false;
        $box->save();

        return $this->success($box->load('room'), 'Box zugewiesen');
    }

    /**
     * Box in Inbox verschieben
     */
    public function moveToInbox(Request $request, $id)
    {
        $box = Box::find($id);
        
        if (!$box) {
            return $this->error('Box nicht gefunden', 404);
        }

        $box->room_id = null;
        $box->is_in_inbox = true;
        $box->save();

        return $this->success($box, 'Box in Inbox verschoben');
    }

    /**
     * QR-Code generieren
     */
    public function generateQrCode(Request $request, $id)
    {
        $box = Box::find($id);
        
        if (!$box) {
            return $this->error('Box nicht gefunden', 404);
        }

        $token = $box->generateQrCode();
        $qrImage = $box->getQrCodeImageBase64();

        return $this->success([
            'qr_token' => $token,
            'qr_code_url' => $box->qr_code_url,
            'qr_code_image' => $qrImage,
        ], 'QR-Code generiert');
    }

    /**
     * Items in der Box
     */
    public function items(Request $request, $id)
    {
        $box = Box::find($id);
        
        if (!$box) {
            return $this->error('Box nicht gefunden', 404);
        }

        $query = $box->items()->with(['category', 'coverImage']);
        
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $items = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return $this->success($items);
    }
}