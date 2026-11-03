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
        Schema::create('anticipos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 30)->unique();
            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('caja_id')
                ->nullable()
                ->constrained('cajas')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('pago_compra_id')
                ->nullable()
                ->unique()
                ->constrained('pagos_compra')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('origen', ['pago_excedente', 'directo'])->default('pago_excedente');
            $table->decimal('monto_original', 14, 2);
            $table->decimal('saldo_disponible', 14, 2);
            $table->enum('estado', ['disponible', 'agotado', 'anulado'])->default('disponible');
            $table->string('metodo_pago', 30);
            $table->string('referencia', 100)->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['proveedor_id', 'estado']);
            $table->index('origen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anticipos_proveedor');
    }
};
