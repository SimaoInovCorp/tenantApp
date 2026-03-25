<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
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
        return [
            'name'    => ['required', 'string', 'max:100'],
            'slug'    => ['nullable', 'string', 'alpha_dash', 'max:60', 'unique:tenants,slug'],
            'plan_id' => ['nullable', 'exists:plans,id'],
        ];
    }
}
