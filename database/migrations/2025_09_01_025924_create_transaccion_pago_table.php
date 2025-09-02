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
        Schema::create('transaccion_pago', function (Blueprint $table) {
            $table->id('id_transaccion_pago');           // INT PK (Auto-incremental)
            $table->unsignedBigInteger('id_reserva_pago');  // INT (FK hacia reserva_pago)
            $table->unsignedBigInteger('id_metodo_pago');  // INT (FK hacia metodo_pago)
            $table->unsignedBigInteger('id_folio');        // INT (FK hacia folio)
            $table->unsignedBigInteger('id_credito');      // INT (FK hacia credito_respaldo)
            $table->unsignedBigInteger('is_tipo_transaccion'); // INT (FK hacia tipo_transaccion)
            $table->string('resultado', 255);               // Resultado de la transacción
            $table->unsignedBigInteger('id_cargo_reserva');  // INT (FK hacia cargo_reserva)
            $table->timestamps();                           // created_at y updated_at

            // Claves foráneas
            $table->foreign('id_reserva_pago')
                  ->references('id_reserva_pago')
                  ->on('reserva_pago')      // Relación con la tabla reserva_pago
                  ->onDelete('cascade');    // Acción al eliminar un pago

            $table->foreign('id_metodo_pago')
                  ->references('id_metodo_pago')
                  ->on('metodo_pago')      // Relación con la tabla metodo_pago
                  ->onDelete('cascade');    // Acción al eliminar un método de pago

            $table->foreign('id_folio')
                  ->references('id_folio')
                  ->on('folio')            // Relación con la tabla folio
                  ->onDelete('cascade');    // Acción al eliminar un folio

            $table->foreign('id_credito')
                  ->references('id_credito')
                  ->on('credito_respaldo') // Relación con la tabla credito_respaldo
                  ->onDelete('cascade');    // Acción al eliminar un crédito respaldo

            $table->foreign('is_tipo_transaccion')
                  ->references('id_tipo_transaccion')
                  ->on('tipo_transaccion') // Relación con la tabla tipo_transaccion
                  ->onDelete('cascade');    // Acción al eliminar un tipo de transacción

            $table->foreign('id_cargo_reserva')
                  ->references('id_cargo')
                  ->on('cargo_reserva')    // Relación con la tabla cargo_reserva
                  ->onDelete('cascade');    // Acción al eliminar un cargo de reserva
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaccion_pago', function (Blueprint $table) {
            // Eliminar las claves foráneas
            $table->dropForeign(['id_reserva_pago']);
            $table->dropForeign(['id_metodo_pago']);
            $table->dropForeign(['id_folio']);
            $table->dropForeign(['id_credito']);
            $table->dropForeign(['is_tipo_transaccion']);
            $table->dropForeign(['id_cargo_reserva']);
        });

        Schema::dropIfExists('transaccion_pago');
    }
};
