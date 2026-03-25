<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Subscription $resource
 */
class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'status'           => $this->status,
            'starts_at'        => $this->starts_at,
            'ends_at'          => $this->ends_at,
            'trial_ends_at'    => $this->trial_ends_at,
            'days_remaining'   => $this->daysRemaining(),
            'is_active'        => $this->isActive(),
            'is_trial'         => $this->isTrial(),
            'is_expired'       => $this->isExpired(),
            'next_billing_date'=> $this->nextBillingDate(),
            'prorated_amount'  => $this->prorated_amount,
            'plan'             => new PlanResource($this->whenLoaded('plan')),
        ];
    }
}
