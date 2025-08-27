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
        Schema::create('reserva', function (Blueprint $table) {
        $table->id('id_reserva');                 // INT PK
        $table->unsignedBigInteger('id_cliente');                    // INT NOT NULL (FK futura)
        $table->unsignedBigInteger('id_estado_res');                 // INT NOT NULL (FK futura)
        $table->dateTime('fecha_creacion');               // NOT NULL
        $table->string('fuente', 20);                     // NOT NULL (web | frontdesk)
        $table->decimal('total_monto_reserva', 10, 2);           // NOT NULL
        $table->string('notas', 300)->nullable();         // NULL
        $table->timestamps();

        // índices opcionales (sin FK):
        $table->index('id_cliente');
        $table->index('id_estado_res');

        // Si quieres CHECKs y tu MySQL los soporta, descomenta:
        //$table->check("fuente IN ('web','frontdesk')");
        //$table->check('total_monto_reserva > 0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva');
    }
};
