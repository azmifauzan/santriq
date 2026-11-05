<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->string('learning_method')->nullable();
            $table->string('level')->nullable();
            $table->unsignedSmallInteger('page_start')->nullable();
            $table->unsignedSmallInteger('page_end')->nullable();
            $table->string('surah')->nullable();
            $table->unsignedSmallInteger('ayah_start')->nullable();
            $table->unsignedSmallInteger('ayah_end')->nullable();
            $table->string('activity_type')->nullable();
            $table->string('fluency')->nullable();
            $table->text('practice_note')->nullable();
            $table->index(['tenant_id', 'student_id', 'achieved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'student_id', 'achieved_at']);
            $table->dropColumn(['learning_method', 'level', 'page_start', 'page_end', 'surah', 'ayah_start', 'ayah_end', 'activity_type', 'fluency', 'practice_note']);
        });
    }
};
