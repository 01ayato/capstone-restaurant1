<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'alias',
        'sequence',
        'is_active',
        'sidebar_expand_children',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sequence' => 'integer',
            'sidebar_expand_children' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Roles allowed to see this module in the sidebar and its pages.
     * When empty, all authenticated users may access (unless enforced elsewhere).
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }
}
