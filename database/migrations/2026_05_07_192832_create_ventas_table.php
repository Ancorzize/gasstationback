<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            $table->string('prefijo', 10)->nullable();
            $table->string('numero_factura', 30)->unique();

            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('tipo_venta', 20);
            // contado | credito | mixta

            $table->string('estado', 20)->default('confirmada');
            // confirmada | anulada

            $table->string('estado_pago', 20)->default('pagado');
            // pagado | pendiente | parcial

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('impuesto', 14, 2)->default(0);
            $table->decimal('soldicom', 14, 2)->default(0);
            $table->decimal('sobre_tasa', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->decimal('total_pagado', 14, 2)->default(0);
            $table->decimal('saldo_pendiente', 14, 2)->default(0);

            $table->dateTime('fecha_venta');

            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->index('cliente_id');
            $table->index('user_id');
            $table->index('tipo_venta');
            $table->index('estado');
            $table->index('estado_pago');
            $table->index('fecha_venta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};