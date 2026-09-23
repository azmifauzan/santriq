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
use Illuminate\Support\Facades\Storage;

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

        $this->resetPublicLandingContent($tenant);

        (new DemoDataSeeder)->run();

        $this->info('Demo tenant reset.');

        return self::SUCCESS;
    }

    /**
     * `LembagaUpdateRequest` now refuses writes to the demo tenant, but this
     * clears out anything set before that guard existed (or any settings
     * key never routed through that form request) — the tagline,
     * description, and any uploaded logo/gallery files are public and
     * otherwise untouched by the reset above.
     */
    private function resetPublicLandingContent(Tenant $tenant): void
    {
        $landing = $tenant->settings['landing'] ?? [];

        foreach ([$landing['logo_path'] ?? null, ...($landing['gallery'] ?? [])] as $path) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }

        $tenant->update([
            'settings' => [...$tenant->settings ?? [], 'landing' => []],
        ]);
    }
}
