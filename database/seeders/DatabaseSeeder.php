<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed one demo lembaga so a fresh install has something to click through.
     *
     * Model events are deliberately left enabled: Student::$qr_token and
     * Guardian::$link_token are generated in "creating" hooks.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'tpq-demo'],
            [
                'name' => 'TPQ Demo SantriQ',
                'address' => 'Jl. Contoh No. 1',
                'phone' => '08123456789',
                'timezone' => 'Asia/Jakarta',
                'settings' => ['dedup_minutes' => 5],
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@santriq.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Admin Demo',
                'password' => 'password',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'pengajar@santriq.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Pengajar Demo',
                'password' => 'password',
                'role' => 'pengajar',
                'email_verified_at' => now(),
            ]
        );

        if (Student::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        Classroom::factory()
            ->count(2)
            ->sequence(['name' => 'Iqro 1'], ['name' => 'Juz Amma'])
            ->create(['tenant_id' => $tenant->id])
            ->each(function (Classroom $classroom) use ($tenant) {
                Student::factory()
                    ->count(5)
                    ->create([
                        'tenant_id' => $tenant->id,
                        'classroom_id' => $classroom->id,
                    ])
                    ->each(function (Student $student) use ($tenant) {
                        $guardian = Guardian::factory()->create([
                            'tenant_id' => $tenant->id,
                            'telegram_chat_id' => null,
                            'linked_at' => null,
                        ]);

                        $student->guardians()->attach($guardian->id, ['relation' => 'Wali']);
                    });
            });
    }
}
