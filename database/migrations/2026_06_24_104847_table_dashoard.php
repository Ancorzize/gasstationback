<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {

            $table->id();

            $table->string('codigo')->unique();

            $table->string('nombre');

            $table->string('tipo',30);

            $table->string('categoria',50)->nullable();

            $table->string('icono',50)->nullable();

            $table->string('color',30)->nullable();

            $table->unsignedTinyInteger('ancho')
                ->default(3);

            $table->unsignedTinyInteger('alto')
                ->default(1);

            $table->text('descripcion')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'dashboard_widgets'
        );
    }
};