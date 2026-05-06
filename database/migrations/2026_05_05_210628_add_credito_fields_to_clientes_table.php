<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('maneja_credito')->default(false)->after('is_active');
            $table->decimal('cupo_credito', 14, 2)->default(0)->after('maneja_credito');
            $table->integer('dias_credito')->nullable()->after('cupo_credito');
            $table->decimal('saldo_credito', 14, 2)->default(0)->after('dias_credito');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'maneja_credito',
                'cupo_credito',
                'dias_credito',
                'saldo_credito',
            ]);
        });
    }
};