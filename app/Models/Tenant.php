<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $logo_url
 * @property string|null $primary_color
 * @property array|null $settings
 * @property int $owner_id
 */
class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'primary_color',
        'settings',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /** The user who owns this tenant. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** All users who belong to this tenant (with role on pivot). */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /** The currently active or trial subscription. */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trial'])
            ->latest();
    }

    /** All subscriptions for history / audit. */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** Onboarding task completion records. */
    public function onboardingTasks(): HasMany
    {
        return $this->hasMany(OnboardingTask::class);
    }

    /** Plan change audit log entries. */
    public function planChangeLogs(): HasMany
    {
        return $this->hasMany(PlanChangeLog::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Filter tenants to only those a given user belongs to.
     *
     * @param  Builder<Tenant>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->whereHas('users', fn (Builder $q) => $q->where('users.id', $user->id));
    }
}
