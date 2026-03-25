<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents actions that would exceed the active plan's numeric limits.
 *
 * Usage on a route: ->middleware(CheckPlanLimit::class . ':max_users')
 *
 * The $limitKey parameter matches a key in Plan::$limits JSON, e.g. 'max_users'.
 * Returns 403 JSON when the limit is reached; passes through otherwise.
 */
class CheckPlanLimit
{
    public function __construct(
        private TenantManager $tenantManager,
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $limitKey): Response
    {
        // Prefer TenantManager (set by ResolveTenant middleware for tenant-scoped routes).
        // Fall back to the route-model-bound {tenant} param (e.g. /tenants/{tenant}/users).
        $tenant = $this->tenantManager->get() ?? $request->route('tenant');

        if ($tenant === null) {
            return response()->json(['message' => 'No active tenant.'], Response::HTTP_FORBIDDEN);
        }

        $subscription = $this->subscriptionService->getActiveSubscription($tenant);

        // If there is no active subscription, use a default restrictive limit of 0
        $limit = $subscription
            ? $subscription->plan->getLimit($limitKey, 0)
            : 0;

        // -1 = unlimited, so we always allow
        if ($limit === -1) {
            return $next($request);
        }

        // Count current resources for the given limit key
        $current = match ($limitKey) {
            'max_users' => $tenant->users()->count(),
            default     => 0,
        };

        if ($current >= $limit) {
            return response()->json([
                'message' => "Plan limit reached for '{$limitKey}'. Upgrade your plan to add more.",
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

