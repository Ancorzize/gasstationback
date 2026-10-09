<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_earnings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_settlement_id')
                ->constrained('payroll_settlements')
                ->cascadeOnDelete();

            $table->string('type_code', 50);
            $table->string('concept_name', 150);
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('unit_value', 12, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_taxable')->default(true);

            $table->timestamps();

            $table->index('payroll_settlement_id');
            $table->index('type_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_earnings');
    }
};
