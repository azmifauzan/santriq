<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can update landing content', function () {
    Storage::fake('public');

    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $response = $admin->put(route('lembaga.update'), [
        'tagline' => 'Belajar Al-Quran sejak dini',
        'description' => 'Deskripsi lembaga.',
        'operating_hours' => 'Senin-Jumat 15.00-17.00',
        'accent_color' => '#059669',
        'logo' => UploadedFile::fake()->image('logo.png'),
        'gallery' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
    ]);

    $response->assertRedirect(route('lembaga.edit'));

    $tenant->refresh();
    expect($tenant->settings['landing']['tagline'])->toBe('Belajar Al-Quran sejak dini');
    expect($tenant->settings['landing']['gallery'])->toHaveCount(2);
    Storage::disk('public')->assertExists($tenant->settings['landing']['logo_path']);
});

test('logo upload rejects svg to prevent stored xss', function () {
    Storage::fake('public');

    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $response = $admin->put(route('lembaga.update'), [
        'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
    ]);

    $response->assertSessionHasErrors('logo');
});

test('pengajar cannot update landing content', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']));

    $pengajar->put(route('lembaga.update'), ['tagline' => 'Nope'])
        ->assertForbidden();
});

test('admin can update institution address and phone', function () {
    $tenant = Tenant::factory()->create(['address' => 'Alamat lama', 'phone' => '0800000000']);
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $response = $admin->put(route('lembaga.update'), [
        'address' => 'Jl. Merdeka No. 1',
        'phone' => '0812345678',
    ]);

    $response->assertRedirect(route('lembaga.edit'));

    $tenant->refresh();
    expect($tenant->address)->toBe('Jl. Merdeka No. 1');
    expect($tenant->phone)->toBe('0812345678');
});
