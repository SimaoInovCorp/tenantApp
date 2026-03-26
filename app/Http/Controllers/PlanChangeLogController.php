<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanChangeLogResource;
use App\Services\TenantManager;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the paginated plan change audit log for the current tenant.
 *
 * Requires 'tenant' middleware — TenantManager must be set.
 */
class PlanChangeLogController extends Controller
{
    public function __construct(private TenantManager $tenantManager) {}

    /** Render the audit log page with paginated plan changes. */
    public function index(): Response
    {
        $tenant = $this->tenantManager->get(); // nullable — no tenant middleware on GET

        $logs = $tenant
            ? $tenant->planChangeLogs()
                ->with(['fromPlan', 'toPlan', 'changedBy'])
                ->orderByDesc('created_at')
                ->paginate(15)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);

        return Inertia::render('Logs/PlanLogs', [
            'logs'      => PlanChangeLogResource::collection($logs),
            'hasTenant' => $tenant !== null,
        ]);
    }
}
