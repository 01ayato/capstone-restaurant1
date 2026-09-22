<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\PasswordResetMail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'role_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'phone',
        'date_of_birth',
        'gender',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->first_name || $user->middle_name || $user->last_name || $user->suffix) {
                $user->name = self::composeDisplayName(
                    $user->first_name,
                    $user->middle_name,
                    $user->last_name,
                    $user->suffix,
                );
            }
        });
    }

    public static function composeDisplayName(
        ?string $first,
        ?string $middle,
        ?string $last,
        ?string $suffix,
    ): string {
        $parts = array_filter([
            $first ? trim($first) : null,
            $middle ? trim($middle) : null,
            $last ? trim($last) : null,
            $suffix ? trim($suffix) : null,
        ]);

        return implode(' ', $parts);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRoleSlug(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRoleSlug(Role::SLUG_SUPERADMIN);
    }

    public function isAdmin(): bool
    {
        return $this->hasRoleSlug(Role::SLUG_ADMIN);
    }

    public function isCashier(): bool
    {
        return $this->hasRoleSlug(Role::SLUG_CASHIER);
    }

    /**
     * Management-level roles (admin + superadmin) get full access to the
     * Management and User Settings areas. Cashiers are excluded.
     */
    public function isManager(): bool
    {
        return in_array($this->role?->slug, [Role::SLUG_ADMIN, Role::SLUG_SUPERADMIN], true);
    }

    public function isUserRole(): bool
    {
        return $this->hasRoleSlug(Role::SLUG_USER);
    }

    public function isStaff(): bool
    {
        return $this->isSuperadmin() || $this->isAdmin() || $this->isCashier();
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasMany<EmailVerificationCode, $this>
     */
    public function emailVerificationCodes(): HasMany
    {
        return $this->hasMany(EmailVerificationCode::class);
    }

    /**
     * Send the password reset link via Brevo instead of Laravel's default notification mailable.
     */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new PasswordResetMail($this, $token));
    }

    /**
     * @return HasMany<Feedback, $this>
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function hasCompletedOrder(): bool
    {
        return $this->orders()->where('status', Order::STATUS_COMPLETED)->exists();
    }

    public function hasCompletedReservation(): bool
    {
        return $this->reservations()->where('status', Reservation::STATUS_COMPLETED)->exists();
    }

    /**
     * Eligible to leave feedback once they have an account and at least one
     * completed order or reservation (either one qualifies).
     */
    public function canLeaveFeedback(): bool
    {
        return $this->hasCompletedOrder() || $this->hasCompletedReservation();
    }

    /**
     * Check if user's email has been verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }
}

