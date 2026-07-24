<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed one demo lembaga so a fresh install has something to click through.
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
