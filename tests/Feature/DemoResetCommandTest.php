<?php

use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\DemoTenant;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('demo:reset is a no-op when the demo tenant does not exist', function () {
    $this->artisan('demo:reset')->assertExitCode(0);

    expect(Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->exists())->toBeFalse();
});

test('demo:reset wipes and reseeds only the demo tenant', function () {
    (new DemoDataSeeder)->run();
    $demoTenant = Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->firstOrFail();
    $staleStudentIds = Student::where('tenant_id', $demoTenant->id)->pluck('id');

    $otherTenant = Tenant::factory()->create(['subdomain' => 'tpq-lain']);
    $otherStudent = Student::factory()->create(['tenant_id' => $otherTenant->id]);

    $admin = User::where('email', 'admin@santriq.test')->firstOrFail();
    $admin->forceFill(['password' => Hash::make('changed-by-visitor')])->save();

    $this->artisan('demo:reset')->assertExitCode(0);

    expect(Student::whereIn('id', $staleStudentIds)->exists())->toBeFalse()
        ->and(Student::where('tenant_id', $demoTenant->id)->count())->toBe(10)
        ->and(Student::where('id', $otherStudent->id)->exists())->toBeTrue();

    $admin->refresh();
    expect(Hash::check('password', $admin->password))->toBeTrue();
});

test('demo:reset clears public landing content a visitor could have defaced', function () {
    Storage::fake('public');

    (new DemoDataSeeder)->run();
    $demoTenant = Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->firstOrFail();

    $logoPath = 'tenants/'.$demoTenant->id.'/logo/defaced.png';
    Storage::disk('public')->put($logoPath, 'fake-image-bytes');

    $demoTenant->update([
        'settings' => [
            ...$demoTenant->settings ?? [],
            'landing' => [
                'tagline' => 'Klaim hadiah gratis Anda sekarang',
                'logo_path' => $logoPath,
            ],
        ],
    ]);

    $this->artisan('demo:reset')->assertExitCode(0);

    $demoTenant->refresh();
    expect($demoTenant->settings['landing'] ?? [])->toBe([]);
    Storage::disk('public')->assertMissing($logoPath);
});
