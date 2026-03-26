<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteUserRequest;
use App\Http\Resources\TenantUserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages user membership within a tenant.
 *
 * All methods use route model binding for {tenant} URL segment.
 */
class TenantUserController extends Controller
{
    public function __construct(private TenantService $tenantService) {}

    /** List all users in the tenant (JSON — used by Vue datatables). */
    public function index(Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        $users = $tenant->users()->withPivot('role')->get();

        return response()->json(TenantUserResource::collection($users));
    }

    /**
     * List all registered app users NOT yet members of this tenant (paginated, 8 per page).
     * Supports an optional ?search=term query to filter by name or email.
     */
    public function available(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        $memberIds = $tenant->users()->pluck('users.id');

        $users = User::whereNotIn('id', $memberIds)
            ->when(
                filled($request->string('search')->trim()),
                fn ($q) => $q->where(function ($q) use ($request): void {
                    $term = '%' . $request->string('search')->trim() . '%';
                    $q->where('name', 'like', $term)
                      ->orWhere('email', 'like', $term);
                })
            )
            ->orderBy('name')
            ->paginate(8);

        return response()->json([
            'data' => $users->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
            ]),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /** Invite an existing user to the tenant by email. */
    public function store(InviteUserRequest $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('inviteUser', $tenant);

        $this->tenantService->attachUser(
            $tenant,
            $request->validated('email'),
            $request->validated('role')
        );

        $users = $tenant->users()->withPivot('role')->get();

        return response()->json(TenantUserResource::collection($users), 201);
    }

    /** Change a member's role (cannot change the owner's role). */
    public function update(Request $request, Tenant $tenant, User $user): JsonResponse
    {
        $this->authorize('inviteUser', $tenant);
        $request->validate(['role' => ['required', 'in:admin,member']]);

        if ($tenant->owner_id === $user->id) {
            abort(422, "The tenant owner's role cannot be changed.");
        }

        $tenant->users()->syncWithoutDetaching([
            $user->id => ['role' => $request->validated('role')],
        ]);

        return response()->json(['success' => true]);
    }

    /** Remove a user from the tenant (owner cannot be removed). */
    public function destroy(Tenant $tenant, User $user): JsonResponse
    {
        $this->authorize('removeUser', $tenant);

        $this->tenantService->detachUser($tenant, $user);

        return response()->json(['success' => true]);
    }
}
