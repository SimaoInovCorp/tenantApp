<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a user when they are invited to join a tenant.
 */
class TenantInvitedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $role,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleLabel = ucfirst($this->role);

        return (new MailMessage)
            ->subject("You've been invited to join {$this->tenant->name} on inoVcorp")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been added to **{$this->tenant->name}** as a **{$roleLabel}**.")
            ->line('You can now access this organization by logging in and switching to it from your dashboard.')
            ->action('Go to Dashboard', url('/dashboard'))
            ->line('If you believe this was a mistake, you can safely ignore this email.');
    }
}
