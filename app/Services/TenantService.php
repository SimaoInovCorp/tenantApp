<?php

namespace App\Services;

use App\Models\OnboardingTask;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantInvitedNotification;
use Illuminate\Support\Str;

/**
 * Handles all business logic related to tenant creation and membership.
 *
 * Single Responsibility: tenant/member management only.
 * No subscription logic lives here — see SubscriptionService.
 */
class TenantService
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Create a new tenant owned by the given user.
     *
     * 1. Generates a unique slug.
     * 2. Persists the Tenant record.
     * 3. Attaches $owner to the pivot with role='owner'.
     * 4. Seeds 3 OnboardingTask rows (all incomplete).
     *
     * @param  array{name?: string, slug?: string|null, logo_url?: string|null, primary_color?: string|null, settings?: array|null}  $data
     */
    public function create(User $owner, array $data): Tenant
    {
        $slug = $this->generateSlug($data['slug'] ?? $data['name'] ?? 'tenant');

        $tenant = Tenant::create([
            'name'          => $data['name'],
            'slug'          => $slug,
            'logo_url'      => $data['logo_url'] ?? null,
            'primary_color' => $data['primary_color'] ?? null,
            'settings'      => $data['settings'] ?? null,
            'owner_id'      => $owner->id,
        ]);

        // Attach owner to the tenant_user pivot
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        // Seed the three onboarding tasks (all incomplete)
        foreach (OnboardingTask::ALL_KEYS as $key) {
            OnboardingTask::create([
                'tenant_id'    => $tenant->id,
                'task_key'     => $key,
                'completed_at' => null,
            ]);
        }

        return $tenant;
    }

    /**
     * Update mutable tenant attributes.
     *
     * @param  array{name?: string, slug?: string|null, logo_url?: string|null, primary_color?: string|null, settings?: array|null}  $data
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        // If slug is being changed, regenerate a unique one unless it's already
        // the tenant's own slug (avoid false conflict).
        if (isset($data['slug']) && $data['slug'] !== $tenant->slug) {
            $data['slug'] = $this->generateSlug($data['slug'], $tenant->id);
        }

        $tenant->update(array_filter($data, fn ($v) => $v !== null));

        return $tenant->refresh();
    }

    /**
     * Invite an existing user to a tenant by their email address.
     *
     * Uses syncWithoutDetaching so a re-invite only updates the role.
     *
     * @throws \RuntimeException When the plan's max_users limit is reached.
     * @throws \InvalidArgumentException When the user is already the owner.
     */
    public function attachUser(Tenant $tenant, string $email, string $role): void
    {
        /** @var User $user */
        $user = User::where('email', $email)->firstOrFail();

        if (! $this->canAttachMoreUsers($tenant)) {
            throw new \RuntimeException('User limit for this plan has been reached.');
        }

        $tenant->users()->syncWithoutDetaching([
            $user->id => ['role' => $role],
        ]);

        $user->notify(new TenantInvitedNotification($tenant, $role));
    }

    /**
     * Remove a user from a tenant.
     *
     * The tenant owner can never be removed.
     *
     * @throws \RuntimeException When attempting to remove the owner.
     */
    public function detachUser(Tenant $tenant, User $user): void
    {
        if ($tenant->owner_id === $user->id) {
            throw new \RuntimeException('The tenant owner cannot be removed.');
        }

        $tenant->users()->detach($user->id);
    }

    /**
     * Check whether the tenant can accommodate one more user given the plan limit.
     *
     * A limit of -1 means unlimited.
     */
    public function canAttachMoreUsers(Tenant $tenant): bool
    {
        $subscription = $tenant->subscription;

        if (! $subscription) {
            // No active subscription — default restrictive (Free = 2 users)
            $maxUsers = 2;
        } else {
            $maxUsers = $subscription->plan->getLimit('max_users', 2);
        }

        if ($maxUsers === -1) {
            return true;
        }

        $current = $tenant->users()->count();

        return $current < $maxUsers;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a unique URL-friendly slug from a string.
     *
     * Appends -2, -3, ... until the slug is unique in the tenants table.
     * Pass $excludeId to ignore the current tenant's own row (for updates).
     */
    private function generateSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId): bool
    {
        $query = Tenant::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
