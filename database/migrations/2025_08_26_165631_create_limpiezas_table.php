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
        Schema::create('limpiezas', function (Blueprint $table) {
            $table->id('id_limpieza');

            $table->string('nombre', 60);
            $table->date('fecha_inicio');
            $table->date('fecha_final')->nullable();

            //$table->unsignedBigInteger('id_usuario_asigna');   // FK (sin constraint por ahora)
            $table->string('descripcion', 250)->nullable();

            //$table->unsignedBigInteger('id_habitacion');
            $table->dateTime('fecha_reporte')->useCurrent();
            $table->string('notas', 250)->nullable();
            $table->string('prioridad', 60)->nullable();

            //$table->unsignedBigInteger('id_usuario_reporta');  // FK (sin constraint por ahora)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('limpiezas');
    }
};
