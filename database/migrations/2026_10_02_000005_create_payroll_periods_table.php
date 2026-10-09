<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_cycle_id')
                ->constrained('payroll_cycles')
                ->restrictOnDelete();

            $table->string('name', 150);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('payment_date');
            $table->string('status', 30)->default('open');

            $table->timestamps();

            $table->index('payroll_cycle_id');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
