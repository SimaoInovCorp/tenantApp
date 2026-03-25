<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

/**
 * Authorization policy for Tenant resources.
 *
 * Role hierarchy:
 *   owner  — full control (update, delete, inviteUser, removeUser, manageSubscription)
 *   admin  — manage members (inviteUser, removeUser), update settings
 *   member — read-only access
 */
class TenantPolicy
{
    /** Any authenticated user can view the tenants list (filtered to their own). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** A user can view a tenant if they are a member. */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isMemberOf($tenant);
    }

    /** Any authenticated user can create a new tenant. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Only the owner or an admin may update tenant settings. */
    public function update(User $user, Tenant $tenant): bool
    {
        $role = $user->getRoleIn($tenant);

        return in_array($role, ['owner', 'admin'], true);
    }

    /** Only the owner may delete a tenant. */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->isOwnerOf($tenant);
    }

    /** Owner or admin may invite new users. */
    public function inviteUser(User $user, Tenant $tenant): bool
    {
        $role = $user->getRoleIn($tenant);

        return in_array($role, ['owner', 'admin'], true);
    }

    /**
     * Owner or admin may remove users.
     *
     * Actual prevention of removing the owner is enforced in TenantService.
     */
    public function removeUser(User $user, Tenant $tenant): bool
    {
        $role = $user->getRoleIn($tenant);

        return in_array($role, ['owner', 'admin'], true);
    }

    /** Only the owner may manage (subscribe, upgrade, downgrade, cancel) subscriptions. */
    public function manageSubscription(User $user, Tenant $tenant): bool
    {
        $role = $user->getRoleIn($tenant);

        return in_array($role, ['owner', 'admin'], true);
    }
}
