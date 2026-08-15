<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerificationCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if code is still valid (not expired and not verified).
     */
    public function isValid(): bool
    {
        return $this->verified_at === null && now()->lt($this->expires_at);
    }

    /**
     * Mark code as verified.
     */
    public function verify(): void
    {
        $this->update(['verified_at' => now()]);
    }

    /**
     * Get the latest unverified code for a user.
     */
    public static function latestPending(int $userId): ?self
    {
        return self::query()
            ->where('user_id', $userId)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();
    }
}
