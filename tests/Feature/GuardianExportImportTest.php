<?php

use App\Models\Guardian;
use App\Models\Tenant;
use App\Models\User;

test('guardian export returns an xlsx file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    Guardian::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Bapak Somad']);

    $response = $this->actingAsStaff($admin)->get(route('guardians.export'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});

test('guardian import creates rows and skips ones missing a name', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $file = makeXlsxUploadedFile(
        ['Nama', 'No. HP'],
        [
            ['Ibu Aisyah', '081234567890'],
            ['', '089999999999'],
        ],
    );

    $response = $this->actingAsStaff($admin)->post(route('guardians.import'), ['file' => $file]);

    $response->assertRedirect();
    $this->assertDatabaseHas('guardians', ['tenant_id' => $tenant->id, 'name' => 'Ibu Aisyah', 'phone' => '081234567890']);
    $this->assertDatabaseCount('guardians', 1);

    $guardian = Guardian::firstWhere('name', 'Ibu Aisyah');
    expect($guardian->link_token)->not->toBeEmpty();
});
