<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Room;
use App\Models\Box;
use App\Models\Category;
use Illuminate\Http\Request;

class ItemController extends BaseApiController
{
    /**
     * Alle Items auflisten
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Item::with([
            'category', 'room', 'box.room', 'coverImage',
            'parentItem.room', 'parentItem.box.room',
            'parentItem.parentItem.room', 'parentItem.parentItem.box.room',
        ]);

        // Kategorie-Filter für Viewer mit konfigurierten Berechtigungen
        if ($user->role === 'viewer') {
            $allowedCategoryIds = $user->categoryPermissions->pluck('id');
            if ($allowedCategoryIds->isNotEmpty()) {
                $query->where(function ($q) use ($allowedCategoryIds) {
                    $q->whereIn('category_id', $allowedCategoryIds)
                      ->orWhereNull('category_id');
                });
            }
        }

        // Status-Filter (Default: nur aktive Items)
        $allowedStatusFilters = array_merge(['alle', 'archiviert'], Item::STATUS_VALUES);
        $statusFilter = $request->get('status', 'aktiv');
        if (!in_array($statusFilter, $allowedStatusFilters)) {
            return $this->error('Ungültiger Status-Filter. Erlaubt: ' . implode(', ', $allowedStatusFilters), 422);
        }
        if ($statusFilter === 'archiviert') {
            $query->archiviert();
        } elseif ($statusFilter !== 'alle') {
            $query->where('status', $statusFilter);
        }

        // Filter
        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->has('box_id')) {
            $query->where('box_id', $request->box_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('in_inbox')) {
            $query->where('is_in_inbox', $request->boolean('in_inbox'));
        }

        // Zubehör-Items standardmässig ausblenden
        if (!$request->boolean('show_accessories', false)) {
            $query->topLevel();
        }

        // Nach parent_item_id filtern
        if ($request->has('parent_item_id')) {
            $query->where('parent_item_id', $request->parent_item_id);
        }

        if ($request->has('warranty_expiring')) {
            $days = (int) $request->get('warranty_expiring', 30);
            $query->warrantyExpiring($days);
        }

        // Suche
        if ($request->has('search')) {
            $term = $request->search;
            
            // Prüfen ob es eine Display-ID ist
            if (preg_match('/^([RBI])(\d+)$/i', $term, $matches)) {
                $type = strtoupper($matches[1]);
                $id = $matches[2];
                
                switch ($type) {
                    case 'I':
                        $item = Item::find($id);
                        if ($item) {
                            return $this->success([
                                'type' => 'single',
                                'data' => $item->load(['category', 'room', 'box']),
                            ]);
                        }
                        break;
                    case 'B':
                        $box = Box::with(['room'])->withCount('items')->find($id);
                        if ($box) {
                            return $this->success([
                                'type' => 'box',
                                'data' => $box,
                            ]);
                        }
                        break;
                    case 'R':
                        $room = Room::withCount(['items', 'boxes'])->find($id);
                        if ($room) {
                            return $this->success([
                                'type' => 'room',
                                'data' => $room,
                            ]);
                        }
                        break;
                }
            }
            
            $query->search($term);
        }

        $items = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return $this->success($items);
    }

    /**
     * Distinct-Werte für Autocomplete (Whitelist-geprüft)
     */
    public function fieldSuggestions(Request $request)
    {
        $allowed = ['brand', 'model', 'purchase_location'];
        $field = $request->get('field');

        if (!in_array($field, $allowed, true)) {
            return $this->error('Ungültiges Feld', 422);
        }

        $values = Item::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->orderBy($field)
            ->pluck($field)
            ->values();

        return $this->success($values);
    }

    /**
     * Inbox-Items auflisten
     */
    public function inbox(Request $request)
    {
        $items = Item::with(['category', 'coverImage'])
            ->where('is_in_inbox', true)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return $this->success($items);
    }

    /**
     * Item erstellen
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'person_id' => 'nullable|exists:persons,id',
            'loaned_to_person_id' => 'nullable|exists:persons,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'article_number' => 'nullable|string|max:255',
            'ean' => 'nullable|string|max:255',
            'inventory_number' => 'nullable|string|max:255',

            // Location (nur eins sollte gesetzt sein)
            'room_id' => 'nullable|exists:rooms,id',
            'box_id' => 'nullable|exists:boxes,id',
            'parent_item_id' => 'nullable|exists:items,id',
            'is_in_inbox' => 'nullable|boolean',

            // Menge / Zustand
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'condition' => 'nullable|string|max:100',
            'status' => 'nullable|in:' . implode(',', Item::STATUS_VALUES),
            'status_datum' => 'nullable|date',
            'status_notiz' => 'nullable|string',
            'notes' => 'nullable|string',

            // Kaufdaten
            'purchased_at' => 'nullable|date',
            'warranty_until' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'purchase_location' => 'nullable|string|max:255',
            'dealer' => 'nullable|string|max:255',
            'order_number' => 'nullable|string|max:255',
            'invoice_present' => 'nullable|boolean',
            'invoice_file' => 'nullable|string',
            
            // Medien
            'image' => 'nullable|string',
        ]);

        // Standort gegenseitig exklusiv
        if ($validated['parent_item_id'] ?? null) {
            $validated['room_id'] = null;
            $validated['box_id'] = null;
            $validated['is_in_inbox'] = false;
        } elseif ($validated['room_id'] ?? null) {
            $validated['box_id'] = null;
            $validated['parent_item_id'] = null;
            $validated['is_in_inbox'] = false;
        } elseif ($validated['box_id'] ?? null) {
            $validated['room_id'] = null;
            $validated['parent_item_id'] = null;
            $validated['is_in_inbox'] = false;
        } else {
            $validated['room_id'] = null;
            $validated['box_id'] = null;
            $validated['parent_item_id'] = null;
            $validated['is_in_inbox'] = true;
        }

        $item = Item::create($validated);

        return $this->success($item->load(['category', 'person', 'loanedToPerson', 'room', 'box']), 'Item erstellt', 201);
    }

    /**
     * Item anzeigen
     */
    public function show(Request $request, $id)
    {
        $item = Item::findByDisplayId($id) ?? Item::find($id);
        
        if (!$item) {
            return $this->error('Item nicht gefunden', 404);
        }

        $item->load([
            'category', 'subcategory', 'person', 'loanedToPerson', 'room', 'box.room', 'documents',
            'parentItem.room', 'parentItem.box.room',
            'parentItem.parentItem.room', 'parentItem.parentItem.box.room',
            'parentItem.parentItem.parentItem.room', 'parentItem.parentItem.parentItem.box.room',
            'childItems' => fn($q) => $q->with(['category', 'coverImage'])->orderBy('name'),
        ]);
        $item->qr_code_image = $item->qr_token ? $item->getQrCodeImageBase64() : null;

        return $this->success($item);
    }

    /**
     * Item bearbeiten
     */
    public function update(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return $this->error('Item nicht gefunden', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'person_id' => 'nullable|exists:persons,id',
            'loaned_to_person_id' => 'nullable|exists:persons,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'article_number' => 'nullable|string|max:255',
            'ean' => 'nullable|string|max:255',
            'inventory_number' => 'nullable|string|max:255',

            'room_id' => 'nullable|exists:rooms,id',
            'box_id' => 'nullable|exists:boxes,id',
            'parent_item_id' => 'nullable|exists:items,id',
            'is_in_inbox' => 'nullable|boolean',

            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'condition' => 'nullable|string|max:100',
            'status' => 'nullable|in:' . implode(',', Item::STATUS_VALUES),
            'status_datum' => 'nullable|date',
            'status_notiz' => 'nullable|string',
            'notes' => 'nullable|string',

            'purchased_at' => 'nullable|date',
            'warranty_until' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'purchase_location' => 'nullable|string|max:255',
            'dealer' => 'nullable|string|max:255',
            'order_number' => 'nullable|string|max:255',
            'invoice_present' => 'nullable|boolean',
            'invoice_file' => 'nullable|string',
            
            'image' => 'nullable|string',
        ]);

        // Leere Strings für FK-Felder auf NULL normalisieren
        foreach (['category_id', 'person_id', 'loaned_to_person_id', 'room_id', 'box_id', 'parent_item_id'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Standort gegenseitig exklusiv + Zirkelbezug-Check
        $locationFields = ['room_id', 'box_id', 'parent_item_id', 'is_in_inbox'];
        $hasAnyLocation = array_intersect_key($validated, array_flip($locationFields));

        if (!empty($hasAnyLocation)) {
            $newParentId = $validated['parent_item_id'] ?? null;
            $newRoomId   = $validated['room_id'] ?? null;
            $newBoxId    = $validated['box_id'] ?? null;

            if ($newParentId) {
                if ($newParentId == $item->id) {
                    return $this->error('Ein Item kann nicht sein eigenes übergeordnetes Item sein.', 422);
                }
                $parentItem = Item::find($newParentId);
                if ($parentItem && $parentItem->isDescendantOf($item->id)) {
                    return $this->error('Zirkelbezug erkannt: Das gewählte übergeordnete Item ist ein Unteritem dieses Items.', 422);
                }
                $validated['room_id']    = null;
                $validated['box_id']     = null;
                $validated['is_in_inbox'] = false;
            } elseif ($newRoomId) {
                $validated['box_id']        = null;
                $validated['parent_item_id'] = null;
                $validated['is_in_inbox']   = false;
            } elseif ($newBoxId) {
                $validated['room_id']        = null;
                $validated['parent_item_id'] = null;
                $validated['is_in_inbox']   = false;
            } else {
                $validated['room_id']        = null;
                $validated['box_id']         = null;
                $validated['parent_item_id'] = null;
                $validated['is_in_inbox']   = true;
            }
        }

        $item->update($validated);

        return $this->success($item->load(['category', 'person', 'loanedToPerson', 'room', 'box']), 'Item aktualisiert');
    }

    /**
     * Item löschen
     */
    public function destroy(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return $this->error('Item nicht gefunden', 404);
        }

        if ($item->childItems()->exists()) {
            return $this->error('Dieses Item kann nicht gelöscht werden, solange es Zubehör-Items enthält. Bitte zuerst die Zubehör-Items umhängen oder löschen.', 422);
        }

        $item->delete();

        return $this->success(null, 'Item gelöscht');
    }

    /**
     * Zubehör-Items (Kinder) eines Items
     */
    public function childItems(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return $this->error('Item nicht gefunden', 404);
        }

        $items = $item->childItems()
            ->with(['category', 'room', 'box.room', 'coverImage',
                'parentItem.room', 'parentItem.box.room'])
            ->orderBy('name')
            ->get();

        return $this->success($items);
    }

    /**
     * Item einem anderen Item zuweisen (Zubehör)
     */
    public function assignToItem(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return $this->error('Item nicht gefunden', 404);
        }

        $request->validate([
            'parent_item_id' => 'required|exists:items,id',
        ]);

        $parentItemId = $request->parent_item_id;

        if ($parentItemId == $item->id) {
            return $this->error('Ein Item kann nicht sein eigenes übergeordnetes Item sein.', 422);
        }

        $parentItem = Item::find($parentItemId);
        if ($parentItem->isDescendantOf($item->id)) {
            return $this->error('Zirkelbezug erkannt.', 422);
        }

        $item->assignToItem($parentItem);

        return $this->success($item->load('parentItem'), 'Item zugewiesen');
    }

    /**
     * Item einem Raum zuweisen
     */
    public function assignToRoom(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return $this->error('Item nicht gefunden', 404);
        }

        $request->validate([
            'room_id' => 'required|exists:rooms,id',
        ]);

        $room = Room::find($request->room_id);
        $item->assignToRoom($room);

        return $this->success($item->load('room'), 'Item zugewiesen');
    }

    /**
     * Item einer Box zuweisen
     */
    public function assignToBox(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return $this->error('Item nicht gefunden', 404);
        }

        $request->validate([
            'box_id' => 'required|exists:boxes,id',
        ]);

        $box = Box::find($request->box_id);
        $item->assignToBox($box);

        return $this->success($item->load('box'), 'Item zugewiesen');
    }

    /**
     * Item in Inbox verschieben
     */
    public function moveToInbox(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return $this->error('Item nicht gefunden', 404);
        }

        $item->moveToInbox();

        return $this->success($item, 'Item in Inbox verschoben');
    }

    /**
     * QR-Code generieren
     */
    public function generateQrCode(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return $this->error('Item nicht gefunden', 404);
        }

        $token = $item->generateQrCode();
        $qrImage = $item->getQrCodeImageBase64();

        return $this->success([
            'qr_token' => $token,
            'qr_code_url' => $item->qr_code_url,
            'qr_code_image' => $qrImage,
        ], 'QR-Code generiert');
    }

    /**
     * Nach QR-Code scannen
     */
    public function scanQrCode(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $item = Item::findByQrToken($request->token);
        if ($item) {
            return $this->success([
                'type' => 'item',
                'data' => $item->load(['category', 'room', 'box']),
            ]);
        }

        $box = Box::findByQrToken($request->token);
        if ($box) {
            return $this->success([
                'type' => 'box',
                'data' => $box->load(['room'])->loadCount('items'),
            ]);
        }

        return $this->error('QR-Code nicht gefunden', 404);
    }
}