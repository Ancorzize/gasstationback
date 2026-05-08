<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->decimal('cantidad', 14, 2);
            $table->decimal('precio_unitario', 14, 2);

            $table->decimal('descuento', 14, 2)->default(0);

            $table->integer('iva')->default(0);
            $table->decimal('iva_valor', 14, 2)->default(0);

            $table->decimal('soldicom', 14, 2)->default(0);
            $table->decimal('sobre_tasa', 14, 2)->default(0);

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->timestamps();

            $table->index('venta_id');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};