<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Plan $resource
 */
class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'price'       => $this->price,
            'interval'    => $this->interval,
            'trial_days'  => $this->trial_days,
            'limits'      => $this->limits,
            'features'    => $this->features,
            'is_active'   => $this->is_active,
        ];
    }
}
