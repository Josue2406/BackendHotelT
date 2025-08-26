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

            $table->string('origen', 30)->unique();
            $table->string('nombre', 30)->unique();
            $table->dateTime('fecha_asignacion');

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
