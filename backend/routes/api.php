<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BoxController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PersonController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LoginLogController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\ItemDocumentController;
use App\Http\Controllers\Api\UserCategoryPermissionController;
use App\Http\Controllers\Admin\BackupController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Berechtigungsmodell:
|   viewer  → nur lesen (GET)
|   editor  → lesen + erstellen + bearbeiten (GET, POST, PUT)
|   admin   → alles (inkl. löschen, Benutzer, Logs)
|
*/

// Öffentliche Routes (kein Auth)
Route::post('/login', [AuthController::class, 'login']);
Route::get('/version', fn() => response()->json(['version' => env('APP_VERSION', 'dev')]));

// Geschützte Routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Profil — jeder eingeloggte User darf eigenes Profil bearbeiten
    Route::put('/profile', [UserController::class, 'updateProfile']);

    // Dashboard — nur lesen
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent', [DashboardController::class, 'recentItems']);
    Route::get('/dashboard/inbox', [DashboardController::class, 'inbox']);
    Route::get('/dashboard/warranties', [DashboardController::class, 'warranties']);
    Route::get('/dashboard/categories', [DashboardController::class, 'categoryDistribution']);
    Route::get('/dashboard/rooms', [DashboardController::class, 'roomDistribution']);

    // Suche — nur lesen
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/quick', [SearchController::class, 'quick']);

    // QR-Code Scan — Leseoperation via POST (alle eingeloggten User)
    Route::post('/scan', [ItemController::class, 'scanQrCode']);

    // Inbox — nur lesen
    Route::get('/inbox/items', [ItemController::class, 'inbox']);
    Route::get('/inbox/boxes', [BoxController::class, 'inbox']);

    // ─── LESEN — alle eingeloggten Benutzer ────────────────────────────

    // Räume
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{id}', [RoomController::class, 'show']);
    Route::get('/rooms/{id}/items', [RoomController::class, 'items']);
    Route::get('/rooms/{id}/boxes', [RoomController::class, 'boxes']);
    Route::get('/rooms/{id}/images', fn(Request $r, $id) => app(ImageController::class)->index('rooms', $id));

    // Boxen
    Route::get('/boxes', [BoxController::class, 'index']);
    Route::get('/boxes/{id}', [BoxController::class, 'show']);
    Route::get('/boxes/{id}/items', [BoxController::class, 'items']);
    Route::get('/boxes/{id}/images', fn(Request $r, $id) => app(ImageController::class)->index('boxes', $id));

    // Items
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/field-suggestions', [ItemController::class, 'fieldSuggestions']);
    Route::get('/items/{id}', [ItemController::class, 'show']);
    Route::get('/items/{id}/images', fn(Request $r, $id) => app(ImageController::class)->index('items', $id));
    Route::get('/items/{id}/documents', [ItemDocumentController::class, 'index']);

    // Ausgeliehene Gegenstände
    Route::get('loans', function () {
        $items = \App\Models\Item::with(['loanedToPerson', 'room', 'box'])
            ->whereNotNull('loaned_to_person_id')
            ->orderBy('name')
            ->get();
        return response()->json(['success' => true, 'data' => $items]);
    });

    // Personen
    Route::get('persons/all', [PersonController::class, 'all']);
    Route::get('/persons', [PersonController::class, 'index']);
    Route::get('/persons/{id}', [PersonController::class, 'show']);

    // Kategorien
    Route::get('/categories/tree', [CategoryController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::get('/categories/{id}/items', [CategoryController::class, 'items']);
    Route::get('/categories/{id}/users', [CategoryController::class, 'users']);

    // ─── SCHREIBEN — admin + editor ────────────────────────────────────
    Route::middleware('role:admin,editor')->group(function () {

        // Räume
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{id}', [RoomController::class, 'update']);
        Route::patch('/rooms/{id}', [RoomController::class, 'update']);
        Route::post('/rooms/{id}/images', fn(Request $r, $id) => app(ImageController::class)->store($r, 'rooms', $id));
        Route::post('/rooms/{id}/images/reorder', fn(Request $r, $id) => app(ImageController::class)->reorder($r, 'rooms', $id));

        // Boxen
        Route::post('/boxes', [BoxController::class, 'store']);
        Route::put('/boxes/{id}', [BoxController::class, 'update']);
        Route::patch('/boxes/{id}', [BoxController::class, 'update']);
        Route::post('/boxes/{id}/assign-room', [BoxController::class, 'assignToRoom']);
        Route::post('/boxes/{id}/move-to-inbox', [BoxController::class, 'moveToInbox']);
        Route::post('/boxes/{id}/qr-code', [BoxController::class, 'generateQrCode']);
        Route::post('/boxes/{id}/images', fn(Request $r, $id) => app(ImageController::class)->store($r, 'boxes', $id));
        Route::post('/boxes/{id}/images/reorder', fn(Request $r, $id) => app(ImageController::class)->reorder($r, 'boxes', $id));

        // Items
        Route::post('/items', [ItemController::class, 'store']);
        Route::put('/items/{id}', [ItemController::class, 'update']);
        Route::patch('/items/{id}', [ItemController::class, 'update']);
        Route::post('/items/{id}/assign-room', [ItemController::class, 'assignToRoom']);
        Route::post('/items/{id}/assign-box', [ItemController::class, 'assignToBox']);
        Route::post('/items/{id}/move-to-inbox', [ItemController::class, 'moveToInbox']);
        Route::post('/items/{id}/qr-code', [ItemController::class, 'generateQrCode']);
        Route::post('/items/{id}/images', fn(Request $r, $id) => app(ImageController::class)->store($r, 'items', $id));
        Route::post('/items/{id}/images/reorder', fn(Request $r, $id) => app(ImageController::class)->reorder($r, 'items', $id));
        Route::post('/items/{id}/documents', [ItemDocumentController::class, 'store']);

        // Personen
        Route::post('/persons', [PersonController::class, 'store']);
        Route::put('/persons/{id}', [PersonController::class, 'update']);
        Route::patch('/persons/{id}', [PersonController::class, 'update']);

        // Kategorien
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::patch('/categories/{id}', [CategoryController::class, 'update']);
    });

    // ─── LÖSCHEN + ADMIN-BEREICH — nur admin ───────────────────────────
    Route::middleware('role:admin')->group(function () {

        // Löschen
        Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
        Route::delete('/boxes/{id}', [BoxController::class, 'destroy']);
        Route::delete('/items/{id}', [ItemController::class, 'destroy']);
        Route::delete('/items/{id}/documents/{documentId}', [ItemDocumentController::class, 'destroy']);
        Route::delete('/images/{imageId}', [ImageController::class, 'destroy']);
        Route::delete('/persons/{id}', [PersonController::class, 'destroy']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Benutzer
        Route::apiResource('users', UserController::class);
        Route::get('/users/{id}/categories', [UserController::class, 'categories']);
        Route::get('/users/{id}/category-permissions', [UserCategoryPermissionController::class, 'index']);
        Route::put('/users/{id}/category-permissions', [UserCategoryPermissionController::class, 'update']);

        // Login-Protokoll
        Route::get('/logs/login', [LoginLogController::class, 'index']);
        Route::get('/logs/login/failed', [LoginLogController::class, 'failed']);
        Route::get('/logs/login/stats', [LoginLogController::class, 'stats']);

        // Backup & Wiederherstellung
        Route::post('/admin/backup/create', [BackupController::class, 'create']);
        Route::post('/admin/backup/preview', [BackupController::class, 'preview']);
        Route::post('/admin/backup/restore', [BackupController::class, 'restore']);
    });
});
