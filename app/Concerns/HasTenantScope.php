<?php

namespace App\Concerns;

use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;

/**
 * Automatically scopes all queries on the model to the active tenant.
 *
 * Add this trait to any model whose every row belongs to exactly one tenant
 * (Subscription, OnboardingTask, PlanChangeLog, etc.).
 *
 * Do NOT apply this to the Tenant model itself — it must be globally queryable.
 */
trait HasTenantScope
{
    /**
     * Boot the trait — registers the global scope.
     *
     * Called automatically by Eloquent via the bootTraitName convention.
     */
    protected static function bootHasTenantScope(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            /** @var TenantManager $manager */
            $manager = app(TenantManager::class);

            // Only apply the scope when a tenant has actually been resolved.
            // This prevents failures during DB seeders, console commands, or
            // routes that intentionally run without a tenant context.
            if ($tenant = $manager->get()) {
                $query->where(static::make()->getTable() . '.tenant_id', $tenant->id);
            }
        });
    }

    /**
     * Convenience: make a fresh model instance to read its table name without
     * instantiating the full Eloquent machinery.
     */
    private static function make(): static
    {
        return new static;
    }
}