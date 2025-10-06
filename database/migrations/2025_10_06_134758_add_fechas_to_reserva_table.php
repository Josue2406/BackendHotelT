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
            // Agregar fechas generales de la reserva
            $table->date('fecha_llegada')->nullable()->after('fecha_creacion');
            $table->date('fecha_salida')->nullable()->after('fecha_llegada');

            // Índice para búsquedas por rango de fechas
            $table->index(['fecha_llegada', 'fecha_salida'], 'idx_reserva_fechas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva', function (Blueprint $table) {
            $table->dropIndex('idx_reserva_fechas');
            $table->dropColumn(['fecha_llegada', 'fecha_salida']);
        });
    }
};
