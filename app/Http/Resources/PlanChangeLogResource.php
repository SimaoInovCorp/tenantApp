<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\PlanChangeLog $resource
 */
class PlanChangeLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'reason'     => $this->reason,
            'created_at' => $this->created_at,
            'from_plan'  => $this->fromPlan ? new PlanResource($this->whenLoaded('fromPlan')) : null,
            'to_plan'    => new PlanResource($this->whenLoaded('toPlan')),
            'changed_by' => $this->whenLoaded('changedBy', fn () => [
                'id'    => $this->changedBy->id,
                'name'  => $this->changedBy->name,
                'email' => $this->changedBy->email,
            ]),
        ];
    }
}
