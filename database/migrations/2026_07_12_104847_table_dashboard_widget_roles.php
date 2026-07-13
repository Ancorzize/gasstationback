<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widget_roles', function (Blueprint $table) {

            $table->id();

            $table->foreignId('dashboard_widget_id')
                ->constrained('dashboard_widgets')
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->integer('orden')
                ->default(1);

            $table->boolean('visible')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'dashboard_widget_id',
                'role_id'
            ]);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'dashboard_widget_roles'
        );
    }
};