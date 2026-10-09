<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            $table->foreignId('compra_id')
                ->nullable()
                ->after('venta_id')
                ->constrained('compras')
                ->nullOnDelete();

            $table->foreignId('gasto_id')
                ->nullable()
                ->after('compra_id')
                ->constrained('gastos')
                ->nullOnDelete();

            $table->foreignId('proveedor_id')
                ->nullable()
                ->after('gasto_id')
                ->constrained('proveedores')
                ->nullOnDelete();

            $table->index('compra_id');
            $table->index('gasto_id');
            $table->index('proveedor_id');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            $table->dropForeign(['compra_id']);
            $table->dropForeign(['gasto_id']);
            $table->dropForeign(['proveedor_id']);

            $table->dropIndex(['compra_id']);
            $table->dropIndex(['gasto_id']);
            $table->dropIndex(['proveedor_id']);

            $table->dropColumn([
                'compra_id',
                'gasto_id',
                'proveedor_id',
            ]);
        });
    }
};
