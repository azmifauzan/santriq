<?php

use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('is_super_admin flag and tenant suspension helpers work', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_super_admin' => true]);

    expect($user->isSuperAdmin())->toBeTrue();
    expect($tenant->isSuspended())->toBeFalse();

    $tenant->update(['suspended_at' => now()]);

    expect($tenant->fresh()->isSuspended())->toBeTrue();
});

test('guest cannot access super admin panel', function () {
    $this->get(route('super-admin.index'))->assertRedirect(route('login'));
});

test('regular tenant admin cannot access super admin panel', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAsStaff($admin)->get(route('super-admin.index'))->assertForbidden();
});

test('super admin sees all tenants with stats on the index page', function () {
    $ownTenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $ownTenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $other = Tenant::factory()->create();
    User::factory()->count(2)->create(['tenant_id' => $other->id, 'role' => 'pengajar']);
    Student::factory()->count(3)->create(['tenant_id' => $other->id]);
    Guardian::factory()->count(4)->create(['tenant_id' => $other->id]);

    $response = $this->actingAsStaff($superAdmin)->get(route('super-admin.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('SuperAdmin/Index')
        ->where('tenants', function ($tenants) use ($other) {
            $row = collect($tenants)->firstWhere('id', $other->id);

            return $row['students_count'] === 3
                && $row['teachers_count'] === 2
                && $row['guardians_count'] === 4;
        })
    );
});

test('super admin sees tenant detail with staff list', function () {
    $ownTenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $ownTenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $other = Tenant::factory()->create();
    $teacher = User::factory()->create(['tenant_id' => $other->id, 'role' => 'pengajar']);

    $response = $this->actingAsStaff($superAdmin)->get(route('super-admin.show', $other));

    $response->assertInertia(fn ($page) => $page
        ->component('SuperAdmin/Show')
        ->where('tenant.id', $other->id)
        ->where('staff.0.id', $teacher->id)
    );
});

test('super admin can suspend and reactivate a tenant', function () {
    $ownTenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $ownTenant->id, 'role' => 'admin', 'is_super_admin' => true]);
    $target = Tenant::factory()->create();

    $this->actingAsStaff($superAdmin)
        ->patch(route('super-admin.toggle-status', $target))
        ->assertRedirect();

    expect($target->fresh()->isSuspended())->toBeTrue();

    $this->actingAsStaff($superAdmin)
        ->patch(route('super-admin.toggle-status', $target))
        ->assertRedirect();

    expect($target->fresh()->isSuspended())->toBeFalse();
});

test('superAdminUrl prop is shared only with super admins', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    $superAdmin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $this->actingAsStaff($admin)
        ->get(route('dashboard', ['subdomain' => $tenant->subdomain]))
        ->assertInertia(fn ($page) => $page->where('superAdminUrl', null));

    $this->actingAsStaff($superAdmin)
        ->get(route('dashboard', ['subdomain' => $tenant->subdomain]))
        ->assertInertia(fn ($page) => $page->where('superAdminUrl', route('super-admin.index')));
});
