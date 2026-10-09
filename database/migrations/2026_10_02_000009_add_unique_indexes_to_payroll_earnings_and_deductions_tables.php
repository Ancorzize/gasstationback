<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_earnings', function (Blueprint $table) {
            $table->unique(['payroll_settlement_id', 'type_code'], 'payroll_earnings_settlement_type_unique');
        });

        Schema::table('payroll_deductions', function (Blueprint $table) {
            $table->unique(['payroll_settlement_id', 'type_code'], 'payroll_deductions_settlement_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_earnings', function (Blueprint $table) {
            $table->dropUnique('payroll_earnings_settlement_type_unique');
        });

        Schema::table('payroll_deductions', function (Blueprint $table) {
            $table->dropUnique('payroll_deductions_settlement_type_unique');
        });
    }
};
