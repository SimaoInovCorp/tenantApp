<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

/**
 * Signature: subscriptions:process-expired
 *
 * Runs daily to mark active/trial subscriptions whose ends_at < now() as expired.
 */
class ProcessExpiredSubscriptions extends Command
{
    protected $signature   = 'subscriptions:process-expired';
    protected $description = 'Mark active subscriptions that have passed their ends_at as expired.';

    public function handle(): int
    {
        $count = Subscription::withoutGlobalScopes()
            ->whereIn('status', ['active', 'trial'])
            ->where('ends_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Marked {$count} subscription(s) as expired.");

        return self::SUCCESS;
    }
}
