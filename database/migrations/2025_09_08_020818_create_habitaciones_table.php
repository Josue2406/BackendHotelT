<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('habitaciones', function (Blueprint $table) {
            $table->bigIncrements('id_habitacion');
            $table->unsignedBigInteger('id_estado_hab');
            $table->unsignedBigInteger('tipo_habitacion_id');
            $table->string('nombre', 50)->unique();
            $table->string('numero', 20);
            $table->smallInteger('piso')->default(1);
            $table->integer('capacidad');
            $table->string('medida', 255);
            $table->string('descripcion', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_estado_hab')->references('id_estado_hab')->on('estado_habitacions')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('tipo_habitacion_id')->references('id_tipo_hab')->on('tipos_habitacion')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('habitacion_amenidads', function (Blueprint $table) {
            $table->bigIncrements('id_amenidad_habitacion');
            $table->unsignedBigInteger('id_habitacion');
            $table->unsignedBigInteger('id_amenidad');
            $table->timestamps();

            $table->foreign('id_habitacion')->references('id_habitacion')->on('habitaciones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('id_amenidad')->references('id_amenidad')->on('amenidads')->cascadeOnUpdate()->restrictOnDelete();

            $table->unique(['id_habitacion', 'id_amenidad'], 'uq_hab_amenidad');
        });
    }

    public function down(): void {
        Schema::dropIfExists('habitacion_amenidads');
        Schema::dropIfExists('habitaciones');
    }
};
