<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->bigIncrements('id_tarifa');
            $table->unsignedBigInteger('id_tipo_habitacion')->nullable();
            $table->unsignedBigInteger('id_temporada')->nullable();
            $table->decimal('precio', 10, 2);
            $table->timestamps();

            $table->foreign('id_tipo_habitacion')->references('id_tipo_hab')->on('tipos_habitacion')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_temporada')->references('id_temporada')->on('temporadas')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void {
        Schema::dropIfExists('tarifas');
    }
};
