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
        Schema::table('reserva_politica', function (Blueprint $table) {
            // Crear la clave foránea para 'id_politica' que apunta a 'politica_cancelacion'
            $table->foreign('id_politica')->references('id_politica')->on('politica_cancelacion')->onDelete('cascade');
            
            // Crear la clave foránea para 'id_reserva' que apunta a 'reserva'
            $table->foreign('id_reserva')->references('id_reserva')->on('reserva')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_politica', function (Blueprint $table) {
            // Eliminar las claves foráneas
            $table->dropForeign(['id_politica']);
            $table->dropForeign(['id_reserva']);
        });
    }
};
