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
        $landing = $tenant->settings['landing'] ?? [];

        return Inertia::render('Tenant/Landing', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'address' => $tenant->address,
                'phone' => $tenant->phone,
            ],
            'landing' => [
                'tagline' => $landing['tagline'] ?? 'Tumbuh dalam ilmu, dekat dalam kebersamaan.',
                'description' => $landing['description'] ?? "{$tenant->name} mendampingi santri belajar Al-Qur'an, bertumbuh dalam adab, dan berkembang bersama.",
                'operating_hours' => $landing['operating_hours'] ?? null,
                'accent_color' => $landing['accent_color'] ?? '#059669',
                'logo_path' => $landing['logo_path'] ?? null,
                'gallery' => $landing['gallery'] ?? [],
            ],
            'stats' => [
                'students' => Student::where('tenant_id', $tenant->id)->where('status', 'active')->count(),
                'teachers' => User::where('tenant_id', $tenant->id)->count(),
                'classrooms' => Classroom::where('tenant_id', $tenant->id)->count(),
            ],
        ]);
    }
}
