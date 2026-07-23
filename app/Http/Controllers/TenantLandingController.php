<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Support\CurrentTenant;
use Inertia\Inertia;
use Inertia\Response;

class TenantLandingController extends Controller
{
    public function show(): Response
    {
        $tenant = CurrentTenant::get();

        return Inertia::render('Tenant/Landing', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'address' => $tenant->address,
                'phone' => $tenant->phone,
            ],
            'landing' => $tenant->settings['landing'] ?? [],
            'stats' => [
                'students' => Student::where('tenant_id', $tenant->id)->where('status', 'active')->count(),
                'teachers' => User::where('tenant_id', $tenant->id)->count(),
                'classrooms' => Classroom::where('tenant_id', $tenant->id)->count(),
            ],
        ]);
    }
}
