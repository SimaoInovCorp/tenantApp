<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a user that their tenant's trial period expires within 3 days.
 *
 * Sent once per subscription — dispatched by CheckTrialExpirations command.
 */
class TrialExpiringNotification extends Notification
{
    use Queueable;

    /** @param Subscription $subscription The trial subscription that is expiring. */
    public function __construct(public readonly Subscription $subscription) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant     = $this->subscription->tenant;
        $daysLeft   = $this->subscription->daysRemaining();
        $planName   = $this->subscription->plan->name;
        $expiryDate = $this->subscription->trial_ends_at?->toFormattedDateString()
            ?? $this->subscription->ends_at->toFormattedDateString();

        return (new MailMessage)
            ->subject("Your {$planName} trial expires in {$daysLeft} day(s) — {$tenant->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The trial for **{$tenant->name}** on the **{$planName}** plan expires on **{$expiryDate}**.")
            ->line("To keep all features and avoid interruption, please upgrade before the trial ends.")
            ->action('Upgrade Now', url('/subscription'))
            ->line('Thank you for using our platform!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'tenant_id'       => $this->subscription->tenant_id,
            'days_remaining'  => $this->subscription->daysRemaining(),
        ];
    }
}


