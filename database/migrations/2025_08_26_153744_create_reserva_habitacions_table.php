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
       Schema::create('reserva_habitacions', function (Blueprint $table) {
    $table->id('id_reserva_hab');
    $table->dateTime('fecha_llegada');
    $table->date('fecha_salida');
    $table->integer('pax_total')->check('pax_total > 0');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_habitacions');
    }
};
