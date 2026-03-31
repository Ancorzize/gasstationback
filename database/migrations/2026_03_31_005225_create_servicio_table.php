<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();

            $table->decimal('precio', 12, 2);

            $table->foreignId('unidad_medida_id')
                ->nullable()
                ->constrained('unidades_medida')
                ->nullOnDelete();

            $table->boolean('permite_decimal')->default(false);
            $table->integer('duracion_minutos')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};