<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_facturacion', function (Blueprint $table) {
            $table->text('matias_api_token')->nullable()->after('ambiente');
            $table->string('matias_sandbox_url', 255)->nullable()->after('matias_api_token');
            $table->boolean('matias_ds_real_transmission')->default(false)->after('matias_sandbox_url');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_facturacion', function (Blueprint $table) {
            $table->dropColumn([
                'matias_api_token',
                'matias_sandbox_url',
                'matias_ds_real_transmission',
            ]);
        });
    }
};
