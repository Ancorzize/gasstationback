<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades_medida', function (Blueprint $table) {
            $table->string('codigo_dian', 20)->nullable()->after('abreviatura');
            $table->string('simbolo_dian', 20)->nullable()->after('codigo_dian');
        });
    }

    public function down(): void
    {
        Schema::table('unidades_medida', function (Blueprint $table) {
            $table->dropColumn(['codigo_dian', 'simbolo_dian']);
        });
    }
};
