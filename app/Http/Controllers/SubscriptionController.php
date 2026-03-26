<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelSubscriptionRequest;
use App\Http\Requests\DowngradeSubscriptionRequest;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpgradeSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the subscription lifecycle for the current tenant.
 *
 * All methods require the 'tenant' middleware to have resolved TenantManager.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private TenantManager $tenantManager
    ) {}

    /** Render the subscription management page. */
    public function show(): Response
    {
        $tenant       = $this->tenantManager->get(); // nullable — no tenant middleware on GET
        $subscription = $tenant
            ? $this->subscriptionService->getActiveSubscription($tenant)
            : null;

        return Inertia::render('Subscription/Show', [
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
        ]);
    }

    /** Subscribe the current tenant to a plan for the first time. */
    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        $tenant = $this->tenantManager->current();
        $this->authorize('manageSubscription', $tenant);
        $plan   = Plan::findOrFail($request->validated('plan_id'));

        try {
            $this->subscriptionService->subscribe($tenant, $plan, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('subscription.show')
            ->with('success', 'Subscription started.');
    }

    /** Upgrade to a higher plan immediately (prorated). */
    public function upgrade(UpgradeSubscriptionRequest $request): RedirectResponse
    {
        $tenant = $this->tenantManager->current();
        $this->authorize('manageSubscription', $tenant);
        $plan   = Plan::findOrFail($request->validated('plan_id'));

        try {
            $this->subscriptionService->upgrade($tenant, $plan, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Plan upgraded successfully.');
    }

    /** Schedule a downgrade to take effect at end of billing cycle. */
    public function downgrade(DowngradeSubscriptionRequest $request): RedirectResponse
    {
        $tenant = $this->tenantManager->current();
        $this->authorize('manageSubscription', $tenant);
        $plan   = Plan::findOrFail($request->validated('plan_id'));

        try {
            $this->subscriptionService->downgrade($tenant, $plan, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Downgrade scheduled for end of billing cycle.');
    }

    /** Cancel the current subscription (access continues until period ends). */
    public function destroy(CancelSubscriptionRequest $request): RedirectResponse
    {
        $tenant = $this->tenantManager->current();
        $this->authorize('manageSubscription', $tenant);

        $this->subscriptionService->cancel(
            $tenant,
            $request->validated('reason') ?? '',
            auth()->user()
        );

        return back()->with('success', 'Subscription cancelled.');
    }
}
