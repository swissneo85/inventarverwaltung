<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Prüfen ob IP gesperrt werden sollte (zu viele Fehlversuche)
        if (LoginLog::isSuspicious($ip, 10, 30)) {
            return $this->error('Zu viele fehlgeschlagene Login-Versuche. Bitte versuchen Sie es später erneut.', 429);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            LoginLog::logFailure($request->username, $ip, $userAgent, 'invalid_credentials');
            return $this->error('Ungültige Anmeldedaten', 401);
        }

        if (!$user->active) {
            LoginLog::logFailure($request->username, $ip, $userAgent, 'account_disabled');
            return $this->error('Account ist deaktiviert', 403);
        }

        // Erfolgreicher Login
        LoginLog::logSuccess($user, $ip, $userAgent);
        
        $user->last_login_at = now();
        $user->save();

        $token = $user->createToken('auth-token')->plainTextToken;

        $user->load('categoryPermissions');

        return $this->success([
            'user' => $user,
            'token' => $token,
            'roles' => [$user->role],
            'category_permissions' => $user->role === 'viewer'
                ? $user->categoryPermissions->pluck('id')
                : null,
        ], 'Login erfolgreich');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logout erfolgreich');
    }

    /**
     * Aktuelle Benutzer-Info
     */
    public function me(Request $request)
    {
        return $this->success($request->user());
    }

    /**
     * Token erneuern
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        $oldToken = $user->currentAccessToken();
        $newToken = $user->createToken('auth-token')->plainTextToken;
        $oldToken->delete();
        
        return $this->success([
            'token' => $newToken,
        ], 'Token erneuert');
    }
}