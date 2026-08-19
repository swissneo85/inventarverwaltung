<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends BaseApiController
{
    /**
     * Alle Benutzer auflisten (Admin)
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error('Keine Berechtigung', 403);
        }

        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $users = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return $this->success($users);
    }

    /**
     * Benutzer erstellen (Admin)
     */
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error('Keine Berechtigung', 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'role' => 'required|in:admin,editor,viewer',
            'kaufpreis_sichtbar' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'active' => true,
            'kaufpreis_sichtbar' => $request->has('kaufpreis_sichtbar')
                ? $request->boolean('kaufpreis_sichtbar')
                : ($request->role === 'admin'),
        ]);

        return $this->success($user, 'Benutzer erstellt', 201);
    }

    /**
     * Benutzer anzeigen
     */
    public function show(Request $request, $id)
    {
        // Nur Admin kann andere Benutzer sehen
        if ($request->user()->id != $id && !$request->user()->isAdmin()) {
            return $this->error('Keine Berechtigung', 403);
        }

        $user = User::find($id);
        
        if (!$user) {
            return $this->error('Benutzer nicht gefunden', 404);
        }

        return $this->success($user);
    }

    /**
     * Benutzer bearbeiten
     */
    public function update(Request $request, $id)
    {
        // Nur Admin kann andere Benutzer bearbeiten
        if ($request->user()->id != $id && !$request->user()->isAdmin()) {
            return $this->error('Keine Berechtigung', 403);
        }

        $user = User::find($id);
        
        if (!$user) {
            return $this->error('Benutzer nicht gefunden', 404);
        }

        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
        ];

        // Nur Admin kann Rollen ändern
        if ($request->user()->isAdmin()) {
            $rules['role'] = 'sometimes|in:admin,editor,viewer';
            $rules['active'] = 'sometimes|boolean';
            $rules['kaufpreis_sichtbar'] = 'sometimes|boolean';
        }

        // Passwort nur wenn angegeben
        if ($request->has('password') && $request->password) {
            $rules['password'] = ['sometimes', Password::min(8)];
        }

        $request->validate($rules);

        $updateData = $request->only(['name', 'email', 'role', 'active']);

        if ($request->user()->isAdmin() && $request->has('kaufpreis_sichtbar')) {
            $updateData['kaufpreis_sichtbar'] = $request->boolean('kaufpreis_sichtbar');
        }
        
        if ($request->has('password') && $request->password) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return $this->success($user, 'Benutzer aktualisiert');
    }

    /**
     * Eigenes Profil aktualisieren
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password|string',
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Passwort-Änderung erfordert altes Passwort
        if ($request->has('password') && $request->password) {
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->error('Aktuelles Passwort ist falsch', 400);
            }
            $user->password = Hash::make($request->password);
        }

        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->save();

        return $this->success($user, 'Profil aktualisiert');
    }

    /**
     * Benutzer löschen (Admin)
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error('Keine Berechtigung', 403);
        }

        $user = User::find($id);
        
        if (!$user) {
            return $this->error('Benutzer nicht gefunden', 404);
        }

        // Admin kann sich nicht selbst löschen
        if ($user->id === $request->user()->id) {
            return $this->error('Sie können sich nicht selbst löschen', 400);
        }

        $user->delete();

        return $this->success(null, 'Benutzer gelöscht');
    }
}