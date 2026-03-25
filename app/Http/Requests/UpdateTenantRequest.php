<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \App\Models\Tenant $tenant */
        $tenant = $this->route('tenant');

        return [
            'name'          => ['nullable', 'string', 'max:100'],
            'slug'          => ['nullable', 'string', 'alpha_dash', 'max:60', 'unique:tenants,slug,' . $tenant->id],
            'logo_url'      => ['nullable', 'url', 'max:500'],
            'logo'          => ['nullable', 'file', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'settings'      => ['nullable', 'array'],
        ];
    }
}
