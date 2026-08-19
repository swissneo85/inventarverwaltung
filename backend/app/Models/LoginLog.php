<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'ip_address',
        'user_agent',
        'success',
        'failure_reason',
        'login_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'login_at' => 'datetime',
        'user_id' => 'integer',
    ];

    /**
     * Benutzer (falls vorhanden bei fehlgeschlagenem Login)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Erfolgreichen Login protokollieren
     */
    public static function logSuccess(User $user, string $ip, string $userAgent): self
    {
        return self::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'success' => true,
            'login_at' => now(),
        ]);
    }

    /**
     * Fehlgeschlagenen Login protokollieren
     */
    public static function logFailure(string $username, string $ip, string $userAgent, string $reason = 'invalid_credentials'): self
    {
        return self::create([
            'user_id' => null,
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'success' => false,
            'failure_reason' => $reason,
            'login_at' => now(),
        ]);
    }

    /**
     * Scopes
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    public function scopeFromIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Verdächtige Aktivität erkennen (zu viele fehlgeschlagene Versuche)
     */
    public static function isSuspicious(string $ip, int $threshold = 5, int $minutes = 15): bool
    {
        $count = self::failed()
            ->fromIp($ip)
            ->where('login_at', '>=', now()->subMinutes($minutes))
            ->count();
        
        return $count >= $threshold;
    }
}