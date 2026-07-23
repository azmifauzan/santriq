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
