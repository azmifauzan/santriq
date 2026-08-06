<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\CardPrintSettings;

test('admin sees default card print settings when none saved', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $response = $admin->get(route('card-print.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/CardPrint')
        ->where('cardPrint', CardPrintSettings::defaults())
        ->where('logoPath', null)
    );
});

test('admin can update card print settings', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $response = $admin->put(route('card-print.update'), [
        'columns_per_print_row' => 3,
        'accent_color' => '#059669',
        'show_nis' => true,
        'show_classroom' => false,
        'show_gender' => true,
        'show_logo' => true,
    ]);

    $response->assertRedirect(route('card-print.edit'));

    $tenant->refresh();
    expect($tenant->settings['card_print'])->toBe([
        'columns_per_print_row' => 3,
        'accent_color' => '#059669',
        'show_nis' => true,
        'show_classroom' => false,
        'show_gender' => true,
        'show_logo' => true,
    ]);
});

test('unchecked boolean fields are saved as false', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $admin->put(route('card-print.update'), [
        'columns_per_print_row' => 2,
        'accent_color' => '#1e293b',
    ]);

    $tenant->refresh();
    expect($tenant->settings['card_print']['show_nis'])->toBeFalse();
    expect($tenant->settings['card_print']['show_classroom'])->toBeFalse();
    expect($tenant->settings['card_print']['show_gender'])->toBeFalse();
    expect($tenant->settings['card_print']['show_logo'])->toBeFalse();
});

test('update rejects invalid columns_per_print_row', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $admin->put(route('card-print.update'), [
        'columns_per_print_row' => 5,
        'accent_color' => '#1e293b',
    ])->assertSessionHasErrors('columns_per_print_row');
});

test('update rejects invalid accent_color', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $admin->put(route('card-print.update'), [
        'columns_per_print_row' => 2,
        'accent_color' => 'not-a-color',
    ])->assertSessionHasErrors('accent_color');
});

test('pengajar cannot update card print settings', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']));

    $pengajar->put(route('card-print.update'), [
        'columns_per_print_row' => 2,
        'accent_color' => '#1e293b',
    ])->assertForbidden();
});
