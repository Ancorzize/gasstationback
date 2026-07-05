<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinos_recaudo', function (Blueprint $table) {

            $table->string('codigo', 20)
                ->after('id')
                ->unique();

        });
    }

    public function down(): void
    {
        Schema::table('destinos_recaudo', function (Blueprint $table) {

            $table->dropUnique(['codigo']);

            $table->dropColumn('codigo');

        });
    }
};