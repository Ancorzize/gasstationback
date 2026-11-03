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
        Schema::create('saldos_iniciales_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->string('numero_documento', 100);
            $table->date('fecha_documento');
            $table->decimal('valor_original', 15, 2);
            $table->decimal('saldo_pendiente', 15, 2);
            $table->enum('estado', ['pendiente', 'parcial', 'pagado', 'anulado'])->default('pendiente');
            $table->text('observacion')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->dateTime('fecha_anulacion')->nullable();
            $table->foreignId('user_anulacion_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['proveedor_id', 'estado']);
            $table->unique(['proveedor_id', 'numero_documento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldos_iniciales_proveedor');
    }
};
