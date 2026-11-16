<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_facturacion', function (Blueprint $table) {
            $table->string('matias_production_url', 255)->nullable()->after('matias_sandbox_url');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_facturacion', function (Blueprint $table) {
            $table->dropColumn('matias_production_url');
        });
    }
};
