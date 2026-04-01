<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciudades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')
                ->constrained('departamentos')
                ->restrictOnDelete();

            $table->string('nombre', 120);
            $table->string('codigo', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['departamento_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciudades');
    }
};