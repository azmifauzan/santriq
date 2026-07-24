<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
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
