<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active Tenant from the X-Tenant-Slug request header.
 *
 * Apply this middleware to all routes that require a tenant context.
 * A missing, invalid, or unauthorized tenant aborts with 404 / 403.
 *
 * Routes that do NOT need this middleware:
 *   - GET  /tenants        (list user's tenants)
 *   - POST /tenants        (create a new tenant)
 *   - POST /switch-tenant  (set active tenant in frontend)
 *   - GET  /plans          (public plan listing)
 */
class ResolveTenant
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-Tenant-Slug');

        if (! $slug) {
            return response()->json(['message' => 'Tenant header missing.'], 400);
        }

        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user || ! $user->isMemberOf($tenant)) {
            abort(403, 'You are not a member of this tenant.');
        }

        $this->tenantManager->set($tenant);

        return $next($request);
    }
}