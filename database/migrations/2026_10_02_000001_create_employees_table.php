<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Relación opcional con usuarios del sistema
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Identificación
            $table->string('worker_code', 50)->unique();
            $table->string('identity_document_id', 20)->default('13');
            $table->string('document_number', 50);

            // Nombres desglosados para Nómina Electrónica / MATIAS API
            $table->string('first_name', 100);
            $table->string('other_names', 100)->nullable();
            $table->string('first_surname', 100);
            $table->string('second_surname', 100)->nullable();

            // Ubicación laboral
            $table->string('working_country_id', 20)->default('45');
            $table->string('work_city_id', 20)->nullable();
            $table->string('work_address', 255);

            // Información laboral
            $table->date('date_entry');
            $table->date('departure_date')->nullable();
            $table->string('worker_type_id', 20)->default('01');
            $table->string('worker_subtype_id', 20)->default('00');
            $table->string('contract_type_id', 20)->default('1');
            $table->decimal('salary', 12, 2)->default(0.00);
            $table->boolean('integral_salary')->default(false);
            $table->boolean('high_risk_pension')->default(false);

            // Estado
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
