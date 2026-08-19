<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BaseApiController extends Controller
{
    /**
     * Erfolg-Antwort
     */
    protected function success($data = null, string $message = 'Erfolgreich', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Fehler-Antwort
     */
    protected function error(string $message = 'Fehler', int $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Entfernt das Kaufpreis-Feld aus Item-Daten, wenn der eingeloggte Benutzer
     * keine Berechtigung zum Sehen des Kaufpreises hat (siehe User::canViewKaufpreis()).
     * Das Feld wird komplett aus der Response entfernt (nicht auf null gesetzt).
     */
    protected function hidePriceIfNeeded(mixed $data): void
    {
        if (auth()->user()->canViewKaufpreis()) return;

        if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data->getCollection()->each->makeHidden('purchase_price');
        } elseif ($data instanceof \Illuminate\Support\Collection) {
            $data->each->makeHidden('purchase_price');
        } elseif ($data instanceof Item) {
            $data->makeHidden('purchase_price');
        }
    }
}
