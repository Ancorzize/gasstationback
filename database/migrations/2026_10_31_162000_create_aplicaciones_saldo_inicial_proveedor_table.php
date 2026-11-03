<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aplicaciones_saldo_inicial_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saldo_inicial_id')->constrained('saldos_iniciales_proveedor')->cascadeOnDelete();
            $table->foreignId('movimiento_caja_id')->nullable()->constrained('movimientos_caja')->restrictOnDelete();
            $table->decimal('valor_aplicado', 15, 2);
            $table->decimal('saldo_anterior', 15, 2);
            $table->decimal('saldo_nuevo', 15, 2);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha_aplicacion');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['saldo_inicial_id', 'fecha_aplicacion'], 'idx_asip_saldo_fecha');
            $table->index('movimiento_caja_id', 'idx_asip_mov_caja');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplicaciones_saldo_inicial_proveedor');
    }
};
