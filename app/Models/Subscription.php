<?php

namespace App\Models;

use App\Concerns\HasTenantScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $plan_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $trial_ends_at
 * @property string $status  active|trial|canceled|expired
 * @property float|null $prorated_amount
 * @property Carbon|null $canceled_at
 * @property string|null $cancel_reason
 * @property-read Plan $plan
 * @property-read Tenant $tenant
 */
class Subscription extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'status',
        'prorated_amount',
        'canceled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'       => 'datetime',
            'ends_at'         => 'datetime',
            'trial_ends_at'   => 'datetime',
            'canceled_at'     => 'datetime',
            'prorated_amount' => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** @param Builder<Subscription> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /** @param Builder<Subscription> $query */
    public function scopeTrial(Builder $query): void
    {
        $query->where('status', 'trial');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /** Days remaining until ends_at (or trial_ends_at when in trial). */
    public function daysRemaining(): int
    {
        $until = $this->isTrial() && $this->trial_ends_at
            ? $this->trial_ends_at
            : $this->ends_at;

        return max(0, (int) now()->diffInDays($until, false));
    }

    /** The next date the subscription renews (null if canceled / expired). */
    public function nextBillingDate(): ?Carbon
    {
        if (in_array($this->status, ['canceled', 'expired'], true)) {
            return null;
        }

        return $this->ends_at;
    }
}
