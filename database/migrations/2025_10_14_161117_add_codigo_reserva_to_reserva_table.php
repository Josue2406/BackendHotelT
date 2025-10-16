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
        Schema::table('reserva', function (Blueprint $table) {
            // Agregar campo para código único de reserva
            $table->string('codigo_reserva', 20)->nullable()->after('id_reserva');

            // Crear índice único para búsquedas rápidas
            $table->unique('codigo_reserva');
        });

        // Nota: Los códigos para reservas existentes se generarán con un comando aparte
        // para no modificar datos existentes durante la migración
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva', function (Blueprint $table) {
            $table->dropUnique(['codigo_reserva']);
            $table->dropColumn('codigo_reserva');
        });
    }
};