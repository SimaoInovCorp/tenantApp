<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property float $price
 * @property string $interval  monthly|yearly
 * @property int $trial_days
 * @property array $limits     e.g. ['max_users' => 5] — use -1 for unlimited
 * @property array $features   e.g. ['reports', 'api_access']
 * @property bool $is_active
 */
class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'interval',
        'trial_days',
        'limits',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'limits'    => 'array',
            'features'  => 'array',
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Check whether this plan includes a named feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }

    /**
     * Retrieve a numeric limit from the plan, returning $default when not set.
     * Pass -1 as the limit value to represent "unlimited".
     */
    public function getLimit(string $key, int $default = 0): int
    {
        return (int) ($this->limits[$key] ?? $default);
    }
}
