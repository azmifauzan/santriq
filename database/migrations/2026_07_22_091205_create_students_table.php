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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('nis');
            $table->string('name');
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->date('birth_date')->nullable();
            $table->string('qr_token')->unique();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'nis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
