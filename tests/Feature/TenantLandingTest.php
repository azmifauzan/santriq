<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('landing page only counts this tenant students, teachers, and classrooms', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-stat']);
    $other = Tenant::factory()->create();

    Student::factory()->count(3)->create(['tenant_id' => $tenant->id, 'status' => 'active']);
    Student::factory()->create(['tenant_id' => $tenant->id, 'status' => 'inactive']);
    Student::factory()->count(5)->create(['tenant_id' => $other->id, 'status' => 'active']);

    User::factory()->count(2)->create(['tenant_id' => $tenant->id]);
    Classroom::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    $this->get("http://{$tenant->subdomain}.santriq.test/")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tenant/Landing')
            ->where('stats.students', 3)
            ->where('stats.teachers', 2)
            ->where('stats.classrooms', 2)
        );
});

test('landing page has useful defaults before its content is configured', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'TPQ Nurul Ilmi',
        'subdomain' => 'tpq-default',
        'settings' => [],
    ]);

    $this->get("http://{$tenant->subdomain}.santriq.test/")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tenant/Landing')
            ->where('landing.tagline', 'Tumbuh dalam ilmu, dekat dalam kebersamaan.')
            ->where('landing.description', "TPQ Nurul Ilmi mendampingi santri belajar Al-Qur'an, bertumbuh dalam adab, dan berkembang bersama.")
            ->where('landing.accent_color', '#059669')
            ->where('landing.gallery', [])
        );
});

test('tenant landing links back to the SantriQ platform', function () {
    $component = file_get_contents(resource_path('js/pages/Tenant/Landing.vue'));

    expect($component)
        ->toContain("import { home, login } from '@/routes';")
        ->and(substr_count($component, ':href="home.url()"'))->toBe(2)
        ->and($component)->not->toContain(':href="home()"')
        ->toContain('Powered by SantriQ');
});
