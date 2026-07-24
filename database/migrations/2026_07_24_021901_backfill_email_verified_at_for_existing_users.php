<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * User::class now implements MustVerifyEmail, which makes the existing
     * 'verified' middleware on staff routes actually enforce. Every account
     * created before this change (admins, pengajar) never went through a
     * verification step, so without this backfill they'd all be locked out
     * of the dashboard on deploy.
     */
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally irreversible: we can't tell which rows were
        // genuinely verified before this ran versus backfilled here.
    }
};
