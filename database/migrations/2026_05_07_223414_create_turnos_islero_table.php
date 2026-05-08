<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos_islero', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estacion_id')
                ->constrained('estaciones')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('fecha_apertura');
            $table->dateTime('fecha_cierre')->nullable();

            $table->string('estado', 20)->default('abierto');
            // abierto | cerrado | anulado

            $table->decimal('total_ventas_combustible', 14, 2)->default(0);
            $table->decimal('total_ventas_lubricantes', 14, 2)->default(0);
            $table->decimal('total_creditos', 14, 2)->default(0);
            $table->decimal('total_abonos', 14, 2)->default(0);

            $table->decimal('pagos_qr', 14, 2)->default(0);
            $table->decimal('pagos_datafono', 14, 2)->default(0);
            $table->decimal('pagos_transferencia', 14, 2)->default(0);
            $table->decimal('pagos_consignacion', 14, 2)->default(0);
            $table->decimal('pagos_efectivo', 14, 2)->default(0);
            $table->decimal('otros_movimientos', 14, 2)->default(0);

            $table->text('otros_movimientos_detalle')->nullable();

            $table->decimal('total_reportado', 14, 2)->default(0);
            $table->decimal('total_sistema', 14, 2)->default(0);
            $table->decimal('balance_final', 14, 2)->default(0);

            $table->text('observacion_apertura')->nullable();
            $table->text('observacion_cierre')->nullable();

            $table->timestamps();

            $table->index('estacion_id');
            $table->index('user_id');
            $table->index('estado');
            $table->index('fecha_apertura');
            $table->index('fecha_cierre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos_islero');
    }
};