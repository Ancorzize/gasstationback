<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precios_combustible', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->decimal('precio', 14, 2);

            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('producto_id');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_combustible');
    }
};