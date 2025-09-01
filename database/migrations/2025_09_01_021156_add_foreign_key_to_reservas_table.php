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
        Schema::table('reserva', function (Blueprint $table) {
            // Cambiar la columna 'id_fuente' de tipo VARCHAR a unsignedBigInteger
            $table->unsignedBigInteger('id_fuente')->nullable();  // Permite valores NULL

            // Crear la clave foránea para 'id_fuente' que apunte a 'fuentes'
            $table->foreign('id_fuente')->references('id_fuente')->on('fuentes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva', function (Blueprint $table) {
            // Eliminar la clave foránea
            $table->dropForeign(['id_fuente']);
            
            // Eliminar la columna 'id_fuente'
            $table->dropColumn('id_fuente');

            // Agregar la columna 'fuente' de vuelta si es necesario
            $table->string('fuente', 20);
        });
    }
};
