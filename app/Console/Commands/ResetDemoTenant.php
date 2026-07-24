<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;
use App\Support\DemoTenant;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDemoTenant extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Wipe and reseed the public demo tenant with fresh sample data';

    public function handle(): int
    {
        $tenant = Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->first();

        if (! $tenant) {
            $this->info('Demo tenant not found, skipping.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($tenant) {
            Guardian::where('tenant_id', $tenant->id)->delete();
            Student::where('tenant_id', $tenant->id)->delete();
            Classroom::where('tenant_id', $tenant->id)->delete();
        });

        (new DemoDataSeeder)->run();

        $this->info('Demo tenant reset.');

        return self::SUCCESS;
    }
}
