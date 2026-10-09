<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_payment_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->string('payment_method_id', 50)->nullable();
            $table->string('means_payment_id', 50)->nullable();
            $table->string('bank', 100)->nullable();
            $table->string('account_type', 50)->nullable();
            $table->string('account_number', 100)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payment_accounts');
    }
};
