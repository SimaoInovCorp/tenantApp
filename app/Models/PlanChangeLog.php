<?php

namespace App\Models;

use App\Concerns\HasTenantScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit record written whenever a subscription changes plan.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $from_plan_id
 * @property int $to_plan_id
 * @property string|null $reason
 * @property int $changed_by_user_id
 * @property Carbon $created_at
 */
class PlanChangeLog extends Model
{
    use HasTenantScope;

    /** Logs are immutable — only created_at, no updated_at. */
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'from_plan_id',
        'to_plan_id',
        'reason',
        'changed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** The plan this subscription was on before the change (null = first subscription). */
    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    /** The plan this subscription moved to. */
    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }

    /** The user who performed the change. */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    // -------------------------------------------------------------------------
    // Boot — auto-set created_at on insert
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (PlanChangeLog $log) {
            $log->created_at = now();
        });
    }
}
