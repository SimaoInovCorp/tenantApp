<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\OnboardingTask $resource
 */
class OnboardingTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'task_key'     => $this->task_key,
            'is_completed' => $this->isCompleted(),
            'completed_at' => $this->completed_at,
        ];
    }
}
