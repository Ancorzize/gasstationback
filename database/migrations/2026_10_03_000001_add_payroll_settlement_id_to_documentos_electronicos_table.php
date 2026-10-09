<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            $table->foreignId('payroll_settlement_id')
                ->nullable()
                ->after('gasto_id')
                ->constrained('payroll_settlements')
                ->nullOnDelete();

            $table->index('payroll_settlement_id');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            $table->dropForeign(['payroll_settlement_id']);
            $table->dropIndex(['payroll_settlement_id']);
            $table->dropColumn('payroll_settlement_id');
        });
    }
};
