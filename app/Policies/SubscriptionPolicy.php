<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

/**
 * Authorization policy for subscription management.
 *
 * Only the tenant owner may start, upgrade, downgrade, or cancel a subscription.
 */
class SubscriptionPolicy
{
    /**
     * Determine if the user may manage (subscribe, upgrade, downgrade, cancel)
     * the subscription for the given tenant.
     *
     * Both the owner and admins can manage subscriptions.
     */
    public function manage(User $user, Tenant $tenant): bool
    {
        $role = $user->getRoleIn($tenant);

        return in_array($role, ['owner', 'admin'], true);
    }
}
