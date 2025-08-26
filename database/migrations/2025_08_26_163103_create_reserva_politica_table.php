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
        Schema::create('reserva_politica', function (Blueprint $table) {
            $table->id('id_reserva_Politica');   // INT PK (AI)
            $table->integer('id_politica');              // INT (FK futura a politica_cancelacion)
            $table->integer('id_reserva');               // INT (FK futura a reserva)
            $table->string('motivo', 200)->nullable();   // NULL

            // índices para búsquedas (opcional, sin FKs)
            $table->index('id_politica');
            $table->index('id_reserva');
        });
    }

    /*Sin llaves foráneas, solo campos e índices.
    Si luego decides que cada (reserva, política) debe ser único, puedes añadir
    $table->unique(['id_reserva','id_politica'], 'uq_reserva_politica');

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_politica');
    }
};
