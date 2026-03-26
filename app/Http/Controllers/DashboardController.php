<?php

namespace App\Http\Controllers;

use App\Http\Resources\OnboardingTaskResource;
use App\Http\Resources\PlanChangeLogResource;
use App\Http\Resources\SubscriptionResource;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the main dashboard with an overview of the current tenant.
 *
 * Requires 'tenant' middleware — TenantManager must be set.
 */
class DashboardController extends Controller
{
    public function __construct(
        private TenantManager $tenantManager,
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Collect and return all dashboard-relevant data as Inertia props.
     * Eager loads prevent N+1 queries.
     */
    public function index(): Response
    {
        // Use get() (nullable) — dashboard is NOT behind the tenant middleware because
        // Fortify redirects here after login via a plain HTTP redirect with no header.
        // The Vue component already handles the no-tenant empty state.
        $tenant = $this->tenantManager->get();

        if ($tenant === null) {
            return Inertia::render('Dashboard', [
                'tenant'       => null,
                'subscription' => null,
                'usage'        => null,
                'recentLogs'   => [],
                'tasks'        => [],
            ]);
        }

        $subscription = $this->subscriptionService->getActiveSubscription($tenant);

        $maxUsers     = $subscription
            ? $subscription->plan->getLimit('max_users', 2)
            : 2;

        $currentUsers = $tenant->users()->count();

        $recentLogs = $tenant->planChangeLogs()
            ->with(['fromPlan', 'toPlan', 'changedBy'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $tasks = $tenant->onboardingTasks()->get();

        return Inertia::render('Dashboard', [
            'tenant'       => [
                'id'   => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
            'usage'        => [
                'current_users' => $currentUsers,
                'max_users'     => $maxUsers,
            ],
            'recentLogs'   => PlanChangeLogResource::collection($recentLogs),
            'tasks'        => OnboardingTaskResource::collection($tasks),
        ]);
    }
}
