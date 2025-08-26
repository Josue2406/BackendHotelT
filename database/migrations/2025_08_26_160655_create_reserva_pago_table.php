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
        Schema::create('reserva_pago', function (Blueprint $table) {
            $table->id('id_reserva_pago');            // INT PK
        $table->integer('id_reserva');                    // INT NOT NULL (FK futura)
        $table->string('tipo', 20);                       // PREPAGO | ANTICIPO | PENALIDAD | DEVOLUCION
        $table->string('metodo', 20);                     // TARJETA | EFECTIVO | TRANSFER | OTRO
        //$table->integer('id_moneda');                     // INT NOT NULL (FK futura)
        $table->decimal('monto', 14, 2);                  // NOT NULL
        $table->string('estado', 20);                     // AUTORIZADO | CAPTURADO | CONFIRMADO | ANULADO
        $table->dateTime('fecha_pago');                   // NOT NULL
        $table->integer('creado_por');                    // id_cliente (o usuario) NOT NULL

        $table->index('id_reserva');
         //$table->index('id_moneda'); Consultar si es necesario

        // Opcional: checks si tu MySQL 8+ los respalda
        // $table->check("tipo IN ('PREPAGO','ANTICIPO','PENALIDAD','DEVOLUCION')");
        // $table->check("metodo IN ('TARJETA','EFECTIVO','TRANSFER','OTRO')");
        // $table->check("estado IN ('AUTORIZADO','CAPTURADO','CONFIRMADO','ANULADO')");
        // $table->check('monto >= 0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_pago');
    }
};
