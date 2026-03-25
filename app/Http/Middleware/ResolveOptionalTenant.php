<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optionally resolves the active tenant from the X-Tenant-Slug header.
 *
 * Unlike ResolveTenant, this middleware never aborts — it simply sets the
 * TenantManager if a valid, authorized tenant slug is present, and moves on.
 *
 * Use on read-only routes that must be accessible both with and without
 * a selected tenant (e.g. GET /plans, GET /subscription, GET /logs/plans).
 */
class ResolveOptionalTenant
{
    public function __construct(private readonly TenantManager $tenantManager) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-Tenant-Slug');

        if ($slug && $request->user()) {
            $tenant = Tenant::where('slug', $slug)->first();

            if ($tenant && $request->user()->isMemberOf($tenant)) {
                $this->tenantManager->set($tenant);
            }
        }

        return $next($request);
    }
}
