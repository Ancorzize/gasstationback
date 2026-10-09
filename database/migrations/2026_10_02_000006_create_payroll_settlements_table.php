<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settlements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_period_id')
                ->constrained('payroll_periods')
                ->restrictOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            // Snapshot histórico del empleado al momento de la liquidación
            $table->string('worker_code', 50);
            $table->string('identity_document_id', 20);
            $table->string('document_number', 50);
            $table->string('first_name', 100);
            $table->string('other_names', 100)->nullable();
            $table->string('first_surname', 100);
            $table->string('second_surname', 100)->nullable();
            $table->string('worker_type_id', 20);
            $table->string('worker_subtype_id', 20);
            $table->string('contract_type_id', 20);
            $table->date('date_entry');
            $table->date('departure_date')->nullable();
            $table->decimal('salary', 12, 2);
            $table->boolean('integral_salary')->default(false);

            // Datos de liquidación
            $table->integer('days_worked');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('total_earned', 12, 2);
            $table->decimal('deductions_total', 12, 2)->default(0.00);
            $table->decimal('total_voucher', 12, 2);

            $table->string('status', 30)->default('draft');

            $table->timestamps();

            // Restricción de unicidad: 1 liquidación por empleado por período
            $table->unique(['payroll_period_id', 'employee_id']);

            // Índices
            $table->index('payroll_period_id');
            $table->index('employee_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settlements');
    }
};
