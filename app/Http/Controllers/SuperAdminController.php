<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
    /**
     * `SESSION_DOMAIN` is deliberately null (see docs/RENCANA-IMPLEMENTASI.md),
     * so a session started on a tenant subdomain is invisible to the apex
     * domain where this panel lives. Hand off through a signed link the apex
     * verifies, same pattern as GoogleAuthController::redirectToTenantLogin.
     */
    public function redirectHandoff(Request $request): RedirectResponse
    {
        $user = $request->user('web');
        abort_unless($user?->isSuperAdmin(), 403);

        $link = URL::temporarySignedRoute(
            'super-admin.verify',
            now()->addMinutes(5),
            ['user' => $user->id]
        );

        return redirect($link);
    }

    public function verifyHandoff(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($user->isSuperAdmin(), 403);

        Auth::guard('web')->login($user);

        return redirect()->route('super-admin.index');
    }

    public function index(): Response
    {
        Gate::authorize('viewAny', Tenant::class);

        $tenants = Tenant::withCount([
            'students' => fn ($query) => $query->withoutGlobalScopes(),
            'guardians' => fn ($query) => $query->withoutGlobalScopes(),
            'users as teachers_count' => fn ($query) => $query->where('role', 'pengajar'),
        ])->latest()->get();

        return Inertia::render('SuperAdmin/Index', [
            'tenants' => $tenants,
        ]);
    }

    public function show(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        $tenant->loadCount([
            'students' => fn ($query) => $query->withoutGlobalScopes(),
            'guardians' => fn ($query) => $query->withoutGlobalScopes(),
            'users as teachers_count' => fn ($query) => $query->where('role', 'pengajar'),
        ]);

        return Inertia::render('SuperAdmin/Show', [
            'tenant' => $tenant,
            'staff' => $tenant->users()->latest()->get(),
        ]);
    }

    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        Gate::authorize('update', $tenant);

        $tenant->update(['suspended_at' => $tenant->isSuspended() ? null : now()]);

        return redirect()->back()->with('success', $tenant->isSuspended()
            ? 'Lembaga berhasil disuspend.'
            : 'Lembaga berhasil diaktifkan kembali.');
    }
}
