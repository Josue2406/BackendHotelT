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
        Schema::table('reserva_pago', function (Blueprint $table) {
            // Crear la clave foránea para 'id_reserva' que apunta a 'reserva'
            $table->foreign('id_reserva')
                  ->references('id_reserva')
                  ->on('reserva')
                  ->onDelete('cascade'); // Acción cuando se elimina la reserva (puedes cambiar a 'set null' si prefieres)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_pago', function (Blueprint $table) {
            // Eliminar la clave foránea
            $table->dropForeign(['id_reserva']);
        });
    }
};
