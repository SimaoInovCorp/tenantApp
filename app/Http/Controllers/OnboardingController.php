<?php

namespace App\Http\Controllers;

use App\Http\Resources\OnboardingTaskResource;
use App\Models\OnboardingTask;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Drives the 3-step onboarding wizard per tenant.
 *
 * Routes require 'tenant' middleware — TenantManager must be set.
 */
class OnboardingController extends Controller
{
    public function __construct(private TenantManager $tenantManager) {}

    /** Render the onboarding wizard page with all tasks. */
    public function index(): Response
    {
        $tenant = $this->tenantManager->current();
        $tasks  = $tenant->onboardingTasks()->get();

        return Inertia::render('Onboarding/Wizard', [
            'tenant' => [
                'id'   => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'tasks' => OnboardingTaskResource::collection($tasks),
        ]);
    }

    /**
     * Mark an onboarding task as completed.
     *
     * HasTenantScope on OnboardingTask ensures only the current tenant's tasks
     * can be resolved via route model binding.
     */
    public function update(Request $request, OnboardingTask $onboardingTask): JsonResponse
    {
        $tenant = $this->tenantManager->current();

        // Extra guard — HasTenantScope should already prevent cross-tenant resolution
        abort_if($onboardingTask->tenant_id !== $tenant->id, 403);

        $onboardingTask->update(['completed_at' => now()]);

        return response()->json(new OnboardingTaskResource($onboardingTask->refresh()));
    }
}
