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
        Schema::create('asignacion_habitacions', function (Blueprint $table) {
            $table->id('id_asignacion');
            //$table->unsignedBigInteger('id_hab');
            //$table->unsignedBigInteger('id_reserva');

            $table->string('origen', 30);
            $table->string('nombre', 30);
            $table->dateTime('fecha_asignacion');
            $table->unsignedInteger('adultos');
            $table->unsignedInteger('ninos');
            $table->unsignedInteger('bebes');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignacion_habitacions');
    }
};
