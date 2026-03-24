<?php

namespace App\Services;

use App\Models\Tenant;
use RuntimeException;

/**
 * Singleton service that holds the currently active Tenant for the request lifecycle.
 *
 * Bound as a singleton in AppServiceProvider so every class that type-hints
 * TenantManager gets the same instance — no static state, fully testable.
 */
class TenantManager
{
    private ?Tenant $tenant = null;

    /**
     * Set the active tenant for this request.
     */
    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the active tenant, or null if none has been resolved yet.
     */
    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Get the active tenant, throwing if none is set.
     *
     * Use this inside tenant-scoped code so a missing tenant causes a loud
     * failure rather than a silent unscoped query.
     *
     * @throws RuntimeException
     */
    public function current(): Tenant
    {
        if ($this->tenant === null) {
            throw new RuntimeException(
                'No active tenant resolved. Ensure the [tenant] middleware is applied to this route.'
            );
        }

        return $this->tenant;
    }

    /**
     * Clear the active tenant (useful in tests).
     */
    public function clear(): void
    {
        $this->tenant = null;
    }
}