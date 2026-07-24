<?php

use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\URL;

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

test('index page includes platform-wide stats and monthly tenant growth', function () {
    $ownTenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $ownTenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $suspended = Tenant::factory()->create(['suspended_at' => now()]);
    // `for()` binds tenant/classroom directly instead of letting Student's own
    // factory (`classroom_id => Classroom::factory()`, which itself defaults
    // `tenant_id => Tenant::factory()`) spin up throwaway tenants that would
    // inflate the global Tenant::count() this test asserts.
    $classroom = Classroom::factory()->for($suspended, 'tenant')->create();
    Student::factory()->count(2)->for($suspended, 'tenant')->for($classroom, 'classroom')->create();
    Guardian::factory()->count(1)->for($suspended, 'tenant')->create();
    User::factory()->create(['tenant_id' => $suspended->id, 'role' => 'pengajar']);
    User::factory()->unverified()->create(['tenant_id' => $suspended->id, 'role' => 'pengajar']);

    $response = $this->actingAsStaff($superAdmin)->get(route('super-admin.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('stats.tenants', 2)
        ->where('stats.active_tenants', 1)
        ->where('stats.suspended_tenants', 1)
        ->where('stats.students', 2)
        ->where('stats.teachers', 2)
        ->where('stats.guardians', 1)
        ->where('stats.registered_users', 3)
        ->where('stats.verified_users', 2)
        ->has('monthlyTenants', 12)
        ->where('monthlyTenants.11.label', ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'][now()->month - 1].' '.now()->year)
        ->where('monthlyTenants', fn ($monthly) => collect($monthly)->sum('count') === 2)
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
        ->assertInertia(fn ($page) => $page->where('superAdminUrl', route('super-admin.redirect', ['subdomain' => $tenant->subdomain])));
});

test('ownDashboardUrl prop is absent on the super admin\'s own tenant subdomain', function () {
    $tenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    // CurrentTenant is already resolved there — no need for a link back to itself.
    $this->actingAsStaff($superAdmin)
        ->get(route('dashboard', ['subdomain' => $tenant->subdomain]))
        ->assertInertia(fn ($page) => $page->where('ownDashboardUrl', null));
});

test('ownDashboardUrl prop points back to the super admin\'s own tenant on the apex domain', function () {
    $tenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    // CurrentTenant never resolves on the apex domain (see ResolveTenantFromDomain).
    $this->actingAs($superAdmin)
        ->get(route('super-admin.index'))
        ->assertInertia(fn ($page) => $page->where(
            'ownDashboardUrl',
            route('dashboard', ['subdomain' => $tenant->subdomain])
        ));
});

test('non-super-admin cannot start the apex handoff', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAsStaff($admin)
        ->get(route('super-admin.redirect', ['subdomain' => $tenant->subdomain]))
        ->assertForbidden();
});

test('super admin handoff redirects to a signed apex verify link', function () {
    $tenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $response = $this->actingAsStaff($superAdmin)
        ->get(route('super-admin.redirect', ['subdomain' => $tenant->subdomain]));

    $response->assertRedirect();
    $location = $response->headers->get('Location');

    expect($location)->toContain('/super-admin/verify/'.$superAdmin->id);
    expect($location)->toContain('signature=');
});

test('visiting a valid signed verify link logs the super admin in on the apex domain without a prior session', function () {
    $tenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $signedUrl = URL::temporarySignedRoute(
        'super-admin.verify',
        now()->addMinutes(5),
        ['user' => $superAdmin->id]
    );

    $this->assertGuest();

    $response = $this->get($signedUrl);

    $response->assertRedirect(route('super-admin.index'));
    $this->assertAuthenticatedAs($superAdmin);
});

test('verify link rejects a non-super-admin even with a validly signed url', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $signedUrl = URL::temporarySignedRoute(
        'super-admin.verify',
        now()->addMinutes(5),
        ['user' => $admin->id]
    );

    $this->get($signedUrl)->assertForbidden();
    $this->assertGuest();
});

test('verify link rejects a tampered signature', function () {
    $tenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $this->get('http://santriq.test/super-admin/verify/'.$superAdmin->id.'?expires=9999999999&signature=invalid')
        ->assertForbidden();
    $this->assertGuest();
});
