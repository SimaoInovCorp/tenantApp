<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanChangeLog;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Handles all business logic for subscriptions.
 *
 * Single Responsibility: subscribe, upgrade, downgrade, cancel.
 * No tenant/membership logic lives here — see TenantService.
 */
class SubscriptionService
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Subscribe a tenant to a plan for the first time.
     *
     * @throws \RuntimeException When the tenant already has an active or trial subscription.
     */
    public function subscribe(Tenant $tenant, Plan $plan, User $actor): Subscription
    {
        if ($this->getActiveSubscription($tenant) !== null) {
            throw new \RuntimeException('Tenant already has an active or trial subscription.');
        }

        $now      = Carbon::now();
        $endsAt   = $this->computeEndsAt($now, $plan);
        $isTrial  = $plan->trial_days > 0;

        $subscription = Subscription::create([
            'tenant_id'      => $tenant->id,
            'plan_id'        => $plan->id,
            'starts_at'      => $now,
            'ends_at'        => $endsAt,
            'trial_ends_at'  => $isTrial ? $now->copy()->addDays($plan->trial_days) : null,
            'status'         => $isTrial ? 'trial' : 'active',
            'prorated_amount'=> null,
            'canceled_at'    => null,
            'cancel_reason'  => null,
        ]);

        $this->logChange($tenant, null, $plan, $actor, null);

        return $subscription;
    }

    /**
     * Immediately upgrade to a higher-priced plan with proration.
     *
     * 1. Calculates prorated charge.
     * 2. Cancels current subscription immediately.
     * 3. Creates a new active subscription starting now, inheriting the old ends_at.
     *
     * @throws \RuntimeException When there is no active subscription to upgrade.
     */
    public function upgrade(Tenant $tenant, Plan $plan, User $actor): Subscription
    {
        $current = $this->getActiveSubscription($tenant);

        if ($current === null) {
            throw new \RuntimeException('No active subscription to upgrade.');
        }

        $prorated = $this->calculateProration($current, $plan);
        $oldEndsAt = $current->ends_at->copy();

        // Cancel the current subscription immediately
        $current->update([
            'status'      => 'canceled',
            'canceled_at' => Carbon::now(),
            'ends_at'     => Carbon::now(),
        ]);

        $subscription = Subscription::create([
            'tenant_id'       => $tenant->id,
            'plan_id'         => $plan->id,
            'starts_at'       => Carbon::now(),
            'ends_at'         => $oldEndsAt,
            'trial_ends_at'   => null,
            'status'          => 'active',
            'prorated_amount' => $prorated,
            'canceled_at'     => null,
            'cancel_reason'   => null,
        ]);

        $this->logChange($tenant, $current->plan, $plan, $actor, null);

        return $subscription;
    }

    /**
     * Schedule a downgrade to take effect at the end of the current billing cycle.
     *
     * The current subscription is kept active until ends_at.
     * A new subscription is then pre-created with status=active starting at ends_at.
     *
     * @throws \RuntimeException When there is no active subscription to downgrade.
     */
    public function downgrade(Tenant $tenant, Plan $plan, User $actor): Subscription
    {
        $current = $this->getActiveSubscription($tenant);

        if ($current === null) {
            throw new \RuntimeException('No active subscription to downgrade.');
        }

        $cycleEnd   = $current->ends_at->copy();
        $nextEndsAt = $this->computeEndsAt($cycleEnd, $plan);

        // Cancel the current subscription at the end of the period
        $current->update([
            'status'      => 'canceled',
            'canceled_at' => Carbon::now(),
            // ends_at stays the same — access continues until that date
        ]);

        $subscription = Subscription::create([
            'tenant_id'       => $tenant->id,
            'plan_id'         => $plan->id,
            'starts_at'       => $cycleEnd,
            'ends_at'         => $nextEndsAt,
            'trial_ends_at'   => null,
            'status'          => 'active',
            'prorated_amount' => null,
            'canceled_at'     => null,
            'cancel_reason'   => 'downgrade scheduled',
        ]);

        $this->logChange($tenant, $current->plan, $plan, $actor, 'downgrade scheduled');

        return $subscription;
    }

    /**
     * Cancel a subscription immediately (access until period ends).
     *
     * ends_at is intentionally preserved so the tenant keeps access.
     *
     * @throws \RuntimeException When there is no active subscription to cancel.
     */
    public function cancel(Tenant $tenant, string $reason, User $actor): Subscription
    {
        $subscription = $this->getActiveSubscription($tenant);

        if ($subscription === null) {
            throw new \RuntimeException('No active subscription to cancel.');
        }

        $subscription->update([
            'status'        => 'canceled',
            'canceled_at'   => Carbon::now(),
            'cancel_reason' => $reason,
            // ends_at deliberately not changed
        ]);

        $this->logChange($tenant, $subscription->plan, $subscription->plan, $actor, $reason);

        return $subscription->refresh();
    }

    /**
     * Retrieve the current active or trial subscription for a tenant.
     */
    public function getActiveSubscription(Tenant $tenant): ?Subscription
    {
        return Subscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trial'])
            ->with('plan')
            ->latest()
            ->first();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Calculate the prorated charge when upgrading mid-cycle.
     *
     * Formula: charge = max(0, newPrice - (oldDailyRate × daysLeft))
     */
    private function calculateProration(Subscription $current, Plan $newPlan): float
    {
        $daysLeft  = (int) Carbon::now()->diffInDays($current->ends_at, absolute: true);
        $dailyRate = (float) $current->plan->price / 30;
        $credit    = $dailyRate * $daysLeft;

        return (float) max(0, (float) $newPlan->price - $credit);
    }

    /**
     * Compute ends_at from a given start date based on the plan's billing interval.
     */
    private function computeEndsAt(\DateTimeInterface $from, Plan $plan): Carbon
    {
        $base = Carbon::instance($from);
        return match ($plan->interval) {
            'yearly' => $base->copy()->addYear(),
            default  => $base->copy()->addMonth(),
        };
    }

    /**
     * Write a PlanChangeLog entry for any subscription state change.
     */
    private function logChange(
        Tenant $tenant,
        ?Plan $fromPlan,
        Plan $toPlan,
        User $actor,
        ?string $reason
    ): void {
        PlanChangeLog::create([
            'tenant_id'           => $tenant->id,
            'from_plan_id'        => $fromPlan?->id,
            'to_plan_id'          => $toPlan->id,
            'reason'              => $reason,
            'changed_by_user_id'  => $actor->id,
        ]);
    }
}
