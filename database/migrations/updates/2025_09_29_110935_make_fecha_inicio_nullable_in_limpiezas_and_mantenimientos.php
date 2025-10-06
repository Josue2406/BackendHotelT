<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('limpiezas', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->change();
            $table->date('fecha_reporte')->nullable()->change();
        });

        //Hacer nullable fecha_inicio en mantenimientos
        Schema::table('mantenimientos', function (Blueprint $table) {
           // $table->date('fecha_inicio')->nullable()->change();
            $table->date('fecha_reporte')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('limpiezas', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable(false)->change();
        });
        Schema::table('mantenimientos', function (Blueprint $table) {
           $table->date('fecha_reporte')->nullable(false)->change();
        });
    }
};
