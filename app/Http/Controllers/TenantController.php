<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\TenantResource;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\SubscriptionService;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages tenant CRUD operations.
 *
 * Authorization: membership check via User helpers.
 * Business logic: delegated entirely to TenantService / SubscriptionService.
 */
class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService,
        private SubscriptionService $subscriptionService
    ) {}

    /** List all tenants the authenticated user belongs to. */
    public function index(): Response
    {
        $tenants = auth()->user()->tenants()->withPivot('role')->get();

        return Inertia::render('Tenants/Index', [
            'tenants' => TenantResource::collection($tenants),
        ]);
    }

    /** Show the create tenant form with available plans. */
    public function create(): Response
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        return Inertia::render('Tenants/Create', [
            'plans' => \App\Http\Resources\PlanResource::collection($plans),
        ]);
    }

    /**
     * Create a new tenant and optionally subscribe to a plan.
     * Redirects back to the tenant list so the frontend can switch context.
     */
    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $tenant = $this->tenantService->create(
            auth()->user(),
            $request->validated()
        );

        if ($request->filled('plan_id')) {
            $plan = Plan::findOrFail($request->plan_id);
            $this->subscriptionService->subscribe($tenant, $plan, auth()->user());
        }

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant created successfully.')
            ->with('new_tenant_slug', $tenant->slug);
    }

    /** Show the settings page for a specific tenant. */
    public function show(Tenant $tenant): Response
    {
        $this->authorize('view', $tenant);

        // Load the pivot so TenantResource can populate current_user_role,
        // which controls the Danger Zone visibility in the frontend.
        $tenant->load(['users' => fn ($q) => $q->where('users.id', auth()->id())->withPivot('role')]);
        $tenantWithPivot = auth()->user()->tenants()->withPivot('role')->find($tenant->id) ?? $tenant;

        $subscription = $this->subscriptionService->getActiveSubscription($tenant);

        return Inertia::render('Tenants/Settings', [
            'tenant'       => new TenantResource($tenantWithPivot),
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
        ]);
    }

    /** Update mutable tenant attributes (including optional logo file upload). */
    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $path            = $request->file('logo')->store('logos', 'public');
            $data['logo_url'] = Storage::url($path);
        }

        unset($data['logo']);

        $this->tenantService->update($tenant, $data);

        return back()->with('success', 'Tenant updated.');
    }

    /** Permanently delete a tenant (owner only). */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->authorize('delete', $tenant);

        $tenant->delete();

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant deleted.');
    }
}
