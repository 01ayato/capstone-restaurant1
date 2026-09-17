<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const SLUG_SUPERADMIN = 'superadmin';

    public const SLUG_ADMIN = 'admin';

    public const SLUG_USER = 'user';

    public const SLUG_CASHIER = 'cashier';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return BelongsToMany<Module, $this>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Page, $this>
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class)->withTimestamps();
    }

    public function isSuperadmin(): bool
    {
        return $this->slug === self::SLUG_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->slug === self::SLUG_ADMIN;
    }

    public function isUser(): bool
    {
        return $this->slug === self::SLUG_USER;
    }
}
