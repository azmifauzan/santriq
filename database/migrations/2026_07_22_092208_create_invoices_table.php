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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('period');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status')->default('unpaid');
            $table->timestamps();

            $table->unique(['student_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
