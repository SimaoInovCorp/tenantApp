<?php

namespace App\Models;

use App\Concerns\HasTenantScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks completion of each onboarding wizard step per tenant.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $task_key  branding|invite_users|set_permissions
 * @property Carbon|null $completed_at
 */
class OnboardingTask extends Model
{
    use HasTenantScope;

    /**
     * Task key constants — avoids magic strings throughout the codebase.
     */
    public const BRANDING = 'branding';
    public const INVITE_USERS = 'invite_users';
    public const SET_PERMISSIONS = 'set_permissions';

    /** All valid task keys in wizard order. */
    public const ALL_KEYS = [
        self::BRANDING,
        self::INVITE_USERS,
        self::SET_PERMISSIONS,
    ];

    protected $fillable = [
        'tenant_id',
        'task_key',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
