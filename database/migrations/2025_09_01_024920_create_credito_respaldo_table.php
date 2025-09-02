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
        Schema::create('credito_respaldo', function (Blueprint $table) {
            $table->id('id_credito');            // INT PK (Auto-incremental)
            $table->unsignedBigInteger('id_reserva_hab');   // INT (FK hacia reserva_habitacions)
            $table->decimal('monto', 10, 2);     // Monto del crédito respaldo
            $table->unsignedBigInteger('id_estado_credito'); // INT (FK hacia estado_credito)
            $table->date('fecha');               // Fecha del crédito respaldo
            $table->timestamps();                // created_at y updated_at

            // Claves foráneas
            $table->foreign('id_reserva_hab')
                  ->references('id_reserva_hab')
                  ->on('reserva_habitacions')  // Corregir el nombre de la tabla aquí
                  ->onDelete('cascade');  // Acción al eliminar un registro en reserva_habitacions

            $table->foreign('id_estado_credito')
                  ->references('id_estado_credito')
                  ->on('estado_credito')  // Relación con la tabla estado_credito
                  ->onDelete('cascade');  // Acción al eliminar un registro en estado_credito
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credito_respaldo', function (Blueprint $table) {
            // Eliminar las claves foráneas
            $table->dropForeign(['id_reserva_hab']);
            $table->dropForeign(['id_estado_credito']);
        });

        Schema::dropIfExists('credito_respaldo');
    }
};
