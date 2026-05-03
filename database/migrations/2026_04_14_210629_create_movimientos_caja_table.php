<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();

            $table->foreignId('caja_id')
                ->constrained('cajas')
                ->cascadeOnDelete();

            $table->string('tipo_movimiento', 20); // ingreso | egreso
            $table->string('categoria_movimiento', 50); // apertura | cierre | venta_combustible | gasto ...
            $table->string('origen_modulo', 50)->nullable(); // caja | ventas | compras | gastos
            $table->unsignedBigInteger('origen_id')->nullable();

            $table->string('medio_pago', 20); 
            $table->decimal('monto', 14, 2);

            $table->text('descripcion')->nullable();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('fecha_movimiento');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};