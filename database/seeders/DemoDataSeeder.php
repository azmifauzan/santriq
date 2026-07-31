<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\DemoTenant;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /** @var array<int, array{name: string, gender: string}> */
    private const STUDENT_NAMES = [
        ['name' => 'Ahmad Fauzan', 'gender' => 'L'],
        ['name' => 'Aisyah Putri', 'gender' => 'P'],
        ['name' => 'Muhammad Rizki', 'gender' => 'L'],
        ['name' => 'Fatimah Azzahra', 'gender' => 'P'],
        ['name' => 'Abdullah Zaki', 'gender' => 'L'],
        ['name' => 'Khadijah Nur', 'gender' => 'P'],
        ['name' => 'Ibrahim Al Fatih', 'gender' => 'L'],
        ['name' => 'Zahra Amelia', 'gender' => 'P'],
        ['name' => 'Umar Faruq', 'gender' => 'L'],
        ['name' => 'Hafshah Salsabila', 'gender' => 'P'],
    ];

    /** @var array<int, string> */
    private const GUARDIAN_NAMES = [
        'Bapak Ahmad Suryadi',
        'Ibu Siti Aminah',
        'Bapak Muhammad Yusuf',
        'Ibu Fatimah Zahra',
        'Bapak Abdul Rahman',
        'Ibu Nur Hidayah',
        'Bapak Hasan Basri',
        'Ibu Aisyah Rahmawati',
        'Bapak Zainal Abidin',
        'Ibu Khadijah Putri',
    ];

    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => DemoTenant::SUBDOMAIN],
            [
                'name' => 'TPQ Demo SantriQ',
                'address' => 'Jl. Contoh No. 1',
                'phone' => '08123456789',
                'timezone' => 'Asia/Jakarta',
                'settings' => ['dedup_minutes' => 5],
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@santriq.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Admin Demo',
                'password' => 'password',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $pengajar = User::updateOrCreate(
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

        $index = 0;

        Classroom::factory()
            ->count(2)
            ->sequence(['name' => 'Iqro 1'], ['name' => 'Juz Amma'])
            ->create(['tenant_id' => $tenant->id])
            ->each(function (Classroom $classroom) use ($tenant, $admin, $pengajar, &$index) {
                for ($i = 0; $i < 5; $i++) {
                    $studentData = self::STUDENT_NAMES[$index];

                    $student = Student::factory()->create([
                        'tenant_id' => $tenant->id,
                        'classroom_id' => $classroom->id,
                        'name' => $studentData['name'],
                        'gender' => $studentData['gender'],
                    ]);

                    $guardian = Guardian::factory()->create([
                        'tenant_id' => $tenant->id,
                        'name' => self::GUARDIAN_NAMES[$index],
                        'telegram_chat_id' => null,
                        'linked_at' => null,
                    ]);

                    $student->guardians()->attach($guardian->id, ['relation' => 'Wali']);

                    $this->seedAttendanceHistory($tenant, $student, $pengajar);
                    $this->seedAchievements($tenant, $student, $pengajar);
                    $this->seedInvoice($tenant, $student, $admin);
                    $this->seedLeaveRequest($tenant, $student);

                    $index++;
                }
            });
    }

    private function seedAttendanceHistory(Tenant $tenant, Student $student, User $recorder): void
    {
        for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
            $date = now()->subDays($daysAgo);

            if ($date->isFriday()) {
                continue;
            }

            $status = fake()->randomElement(['hadir', 'hadir', 'hadir', 'hadir', 'sakit', 'izin']);

            Attendance::factory()->create([
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'date' => $date->format('Y-m-d'),
                'checked_in_at' => $status === 'hadir'
                    ? $date->clone()->setTime(15, fake()->numberBetween(30, 59))
                    : null,
                'checked_out_at' => null,
                'status' => $status,
                'recorded_by' => $recorder->id,
            ]);
        }
    }

    private function seedAchievements(Tenant $tenant, Student $student, User $recorder): void
    {
        Achievement::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'recorded_by' => $recorder->id,
        ]);
    }

    private function seedInvoice(Tenant $tenant, Student $student, User $verifier): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'period' => now()->format('Y-m'),
            'status' => fake()->boolean(60) ? 'paid' : 'unpaid',
        ]);

        if ($invoice->status === 'paid') {
            Payment::factory()->create([
                'invoice_id' => $invoice->id,
                'verified_by' => $verifier->id,
            ]);
        }
    }

    private function seedLeaveRequest(Tenant $tenant, Student $student): void
    {
        if (! fake()->boolean(30)) {
            return;
        }

        LeaveRequest::factory()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
        ]);
    }
}
