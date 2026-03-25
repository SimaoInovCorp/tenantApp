<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Console\Command;

/**
 * Signature: trials:check-expiring
 *
 * Runs daily to warn tenant owners when their trial expires within 3 days.
 * Also transitions subscriptions where trial_ends_at < now() to 'expired'.
 */
class CheckTrialExpirations extends Command
{
    protected $signature   = 'trials:check-expiring';
    protected $description = 'Notify tenant owners of trials expiring within 3 days and expire past-due trials.';

    public function handle(): int
    {
        // 1. Notify owners of trials expiring in ≤ 3 days
        $expiringSoon = Subscription::withoutGlobalScopes()
            ->where('status', 'trial')
            ->where('trial_ends_at', '<=', now()->addDays(3))
            ->where('trial_ends_at', '>', now())
            ->with(['tenant', 'plan', 'tenant.owner'])
            ->get();

        foreach ($expiringSoon as $subscription) {
            $owner = $subscription->tenant->owner;

            if ($owner instanceof User) {
                $owner->notify(new TrialExpiringNotification($subscription));
                $this->line("Notified {$owner->email} — trial ends {$subscription->trial_ends_at}");
            }
        }

        // 2. Expire trials that have already ended
        $expired = Subscription::withoutGlobalScopes()
            ->where('status', 'trial')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expired as $subscription) {
            $subscription->update(['status' => 'expired']);
            $this->line("Expired trial subscription ID {$subscription->id}");
        }

        $this->info("Processed: {$expiringSoon->count()} warnings, {$expired->count()} expirations.");

        return self::SUCCESS;
    }
}
