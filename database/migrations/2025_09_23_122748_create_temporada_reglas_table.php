<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('temporada_reglas', function (Blueprint $table) {
      $table->id('id_regla');
      $table->unsignedBigInteger('id_temporada');
      $table->enum('scope', ['HOTEL','TIPO','HABITACION']); // ámbito de aplicación
      $table->unsignedBigInteger('tipo_habitacion_id')->nullable(); // si scope=TIGO
      $table->unsignedBigInteger('habitacion_id')->nullable();      // si scope=HABITACION

      $table->enum('tipo_ajuste', ['PORCENTAJE','MONTO']); // % o ₡/$ (permite negativos p/ descuento)
      $table->decimal('valor', 10, 2);                     // 15.00 = +15% ; 10000 = +₡10,000
      $table->tinyInteger('prioridad')->default(1);        // mayor prioridad = gana en empates

      // Opcionales útiles
      $table->set('aplica_dow', ['1','2','3','4','5','6','7'])->nullable(); // 1=Lunes … 7=Domingo
      $table->unsignedInteger('min_noches')->nullable();                     // estadía mínima para que aplique

      $table->timestamps();

      $table->foreign('id_temporada')->references('id_temporada')->on('temporadas')->onDelete('cascade');

      // Índices útiles
      $table->index(['scope','tipo_habitacion_id']);
      $table->index(['scope','habitacion_id']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('temporada_reglas');
  }
};
