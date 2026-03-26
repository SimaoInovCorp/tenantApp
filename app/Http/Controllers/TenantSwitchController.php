<?php

namespace App\Http\Controllers;

use App\Http\Resources\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Switches the active tenant for the authenticated user.
 *
 * Intentionally does NOT use the 'tenant' middleware.
 * Returns JSON so the frontend Pinia store can update.
 */
class TenantSwitchController extends Controller
{
    /**
     * Validate membership and return the TenantResource with pivot role.
     * The frontend stores this in Pinia and adds X-Tenant-Slug to all requests.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => ['required', 'exists:tenants,slug'],
        ]);

        // Load via user relationship to obtain the pivot (role) in one query
        $tenant = auth()->user()
            ->tenants()
            ->where('tenants.slug', $request->slug)
            ->withPivot('role')
            ->first();

        abort_if($tenant === null, 403, 'You are not a member of this tenant.');

        return response()->json(new TenantResource($tenant));
    }
}
