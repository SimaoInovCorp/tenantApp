<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlanChangeLogController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantSwitchController;
use App\Http\Controllers\TenantUserController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// ── Public: pricing page (no auth required) ──────────────────────────────────
// tenant.optional resolves X-Tenant-Slug when present (authenticated), so the
// subscription prop can be populated for logged-in users.
Route::get('plans', [PlanController::class, 'index'])->middleware('tenant.optional')->name('plans.index');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Switch active tenant (no 'tenant' middleware — this sets the active tenant)
    Route::post('api/switch-tenant', [TenantSwitchController::class, 'store'])
        ->name('tenant.switch');

    // Tenant CRUD (no 'tenant' middleware — operates on tenants themselves)
    Route::resource('tenants', TenantController::class)
        ->except(['edit']);

    // Tenant member management (route-model bound {tenant})
    Route::prefix('tenants/{tenant}/users')->name('tenant.users.')->group(function () {
        Route::get('/',           [TenantUserController::class, 'index'])    ->name('index');
        Route::get('/available',  [TenantUserController::class, 'available'])->name('available');
        Route::post('/',          [TenantUserController::class, 'store'])    ->name('store')
            ->middleware('plan.limit:max_users');
        Route::put('/{user}',     [TenantUserController::class, 'update'])   ->name('update');
        Route::delete('/{user}',  [TenantUserController::class, 'destroy'])  ->name('destroy');
    });

    // Dashboard — outside 'tenant' middleware: Fortify redirects here after login via a
    // plain HTTP redirect (no Inertia navigation), so no X-Tenant-Slug header is present.
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Read-only tenant views — outside strict 'tenant' middleware so they render
    // even before a tenant is selected. 'tenant.optional' silently resolves the
    // tenant from X-Tenant-Slug when present, powering subscription/log data.
    Route::get('subscription', [SubscriptionController::class, 'show'])->middleware('tenant.optional')->name('subscription.show');
    Route::get('logs/plans',   [PlanChangeLogController::class, 'index'])->middleware('tenant.optional')->name('logs.plans');

    // ── Tenant-scoped mutations (require X-Tenant-Slug header via 'tenant' middleware) ──
    Route::middleware('tenant')->group(function () {

        // Subscription lifecycle mutations
        Route::prefix('subscription')->name('subscription.')->group(function () {
            Route::post('/',        [SubscriptionController::class, 'store'])   ->name('store');
            Route::put('/upgrade',  [SubscriptionController::class, 'upgrade']) ->name('upgrade');
            Route::put('/downgrade',[SubscriptionController::class, 'downgrade'])->name('downgrade');
            Route::delete('/',      [SubscriptionController::class, 'destroy']) ->name('destroy');
        });

        // Onboarding wizard
        Route::get('onboarding',                    [OnboardingController::class, 'index']) ->name('onboarding.index');
        Route::put('onboarding/{onboardingTask}',   [OnboardingController::class, 'update'])->name('onboarding.update');
    });
});

require __DIR__.'/settings.php';

