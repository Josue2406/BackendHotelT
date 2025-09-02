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
        Schema::table('reserva_servicio', function (Blueprint $table) {
            // Crear la clave foránea para 'id_reserva' que apunta a 'reserva'
            $table->foreign('id_reserva')
                  ->references('id_reserva')
                  ->on('reserva')
                  ->onDelete('cascade'); // Acción cuando se elimina una reserva
                  
                  $table->foreign('id_servicio')
                  ->references('id_servicio')
                  ->on('servicio')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_servicio', function (Blueprint $table) {
            // Eliminar la clave foránea
            $table->dropForeign(['id_reserva']);
            $table->dropForeign(['id_servicio']);
        });
    }
};
