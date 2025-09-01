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
        Schema::create('folio', function (Blueprint $table) {
            $table->id('id_folio');               // INT PK (Auto-incremental)
            $table->unsignedBigInteger('id_reserva_hab');  // INT (FK hacia reserva_habitacions)
            $table->unsignedBigInteger('id_estado_folio'); // INT (FK hacia estado_folio)
            $table->decimal('total', 10, 2);      // Total del folio (monto total)
            $table->timestamps();                 // created_at y updated_at

            // Claves foráneas
            $table->foreign('id_reserva_hab')
                  ->references('id_reserva_hab')
                  ->on('reserva_habitacions')  // Relación con la tabla reserva_habitacions
                  ->onDelete('cascade');       // Acción cuando se elimina un registro en reserva_habitacions

            $table->foreign('id_estado_folio')
                  ->references('id_estado_folio')
                  ->on('estado_folio')        // Relación con la tabla estado_folio
                  ->onDelete('cascade');       // Acción cuando se elimina un registro en estado_folio
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folio', function (Blueprint $table) {
            // Eliminar las claves foráneas
            $table->dropForeign(['id_reserva_hab']);
            $table->dropForeign(['id_estado_folio']);
        });

        Schema::dropIfExists('folio');
    }
};
