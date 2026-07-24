<?php

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
