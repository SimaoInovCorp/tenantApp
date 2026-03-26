<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Returns the list of available plans for the pricing page.
 *
 * No auth or tenant middleware required — plans are public information.
 * When an authenticated user with an active tenant visits, we also pass the
 * current subscription so the CTA buttons can show the correct label and action.
 */
class PlanController extends Controller
{
    public function __construct(
        private TenantManager $tenantManager,
        private SubscriptionService $subscriptionService
    ) {}

    /** Render the Plans/Index page with all active plans. */
    public function index(): Response
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        $tenant       = $this->tenantManager->get();
        $subscription = $tenant
            ? $this->subscriptionService->getActiveSubscription($tenant)
            : null;

        return Inertia::render('Plans/Index', [
            'plans'        => PlanResource::collection($plans),
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
        ]);
    }
}
