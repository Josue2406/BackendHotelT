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
        Schema::table('historial_reserva', function (Blueprint $table) {
            // Crear la clave foránea para 'id_reserva' que apunta a 'reserva'
            $table->foreign('id_reserva')
                  ->references('id_reserva')
                  ->on('reserva')
                  ->onDelete('cascade'); // Acción cuando se elimina la reserva

            // Crear la clave foránea para 'id_usuario' que apunta a 'usuarios'
            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('users')
                  ->onDelete('cascade'); // Acción cuando se elimina un usuario
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_reserva', function (Blueprint $table) {
            // Eliminar las claves foráneas
            $table->dropForeign(['id_reserva']);
            $table->dropForeign(['id_usuario']);
        });
    }
};
