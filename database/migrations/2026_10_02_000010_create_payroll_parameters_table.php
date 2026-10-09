<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_parameters', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unsigned()->unique();
            $table->decimal('smlmv', 12, 2);
            $table->decimal('transport_assistance', 12, 2);
            $table->decimal('max_smlmv_for_transport', 5, 2)->default(2.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_parameters');
    }
};
