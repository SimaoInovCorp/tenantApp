<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Tenant $resource
 */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'logo_url'          => $this->logo_url,
            'primary_color'     => $this->primary_color,
            'settings'          => $this->settings,
            'owner_id'          => $this->owner_id,
            'current_user_role' => $this->whenPivotLoaded(
                'tenant_user',
                fn () => $this->pivot->role
            ),
        ];
    }
}
