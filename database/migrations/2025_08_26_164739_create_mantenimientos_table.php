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
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id('id_mantenimiento');

            $table->string('nombre', 50)->unique();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_final')->nullable();

            //$table->unsignedBigInteger('id_usuario_asigna');
            $table->string('descripcion', 250)->nullable();

            //$table->unsignedBigInteger('id_habitacion');
            $table->dateTime('fecha_reporte')->useCurrent();
            $table->string('notas', 250)->nullable();
            $table->string('prioridad', 50)->nullable();

            //$table->unsignedBigInteger('id_usuario_reporta');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
