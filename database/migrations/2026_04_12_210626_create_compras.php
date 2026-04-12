<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->restrictOnDelete();

            $table->foreignId('bodega_id')
                ->constrained('bodegas')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('numero_documento', 100)->nullable();
            $table->date('fecha_compra');
            $table->date('fecha_vencimiento')->nullable();

            $table->string('tipo_pago', 20); // contado | credito
            $table->string('estado', 20)->default('borrador'); // borrador | confirmada
            $table->string('estado_pago', 20)->default('pendiente'); // pendiente | pagado

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('impuesto', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('total_pagado', 14, 2)->default(0);
            $table->decimal('saldo_pendiente', 14, 2)->default(0);

            $table->text('observacion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};