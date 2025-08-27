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
        Schema::create('reserva_servicio', function (Blueprint $table) {
            $table->id('id_reserva_serv');            // INT PK
        $table->unsignedBigInteger('id_reserva');                    // INT NOT NULL (FK futura)
        $table->unsignedBigInteger('id_servicio');                   // INT NOT NULL (FK futura)
        $table->integer('cantidad');                      // NOT NULL, > 0
        $table->decimal('precio_unitario', 10, 2);        // NOT NULL, >= 0
        $table->string('descripcion', 200)->nullable();   // NULL

        $table->index('id_reserva');
        $table->index('id_servicio');
        $table->timestamps();

        // Si cada servicio debe ser único por reserva, habilita:
        $table->unique(['id_reserva','id_servicio'], 'uq_reserva_servicio');

        // Opcional CHECKs:
        // $table->check('cantidad > 0');
        // $table->check('precio_unitario >= 0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_servicio');
    }
};
