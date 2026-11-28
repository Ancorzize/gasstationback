<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'qr_login_token')) {
                $table->string('qr_login_token', 64)
                    ->nullable()
                    ->unique()
                    ->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'qr_login_token')) {
                $table->dropUnique(['qr_login_token']);
                $table->dropColumn('qr_login_token');
            }
        });
    }
};
