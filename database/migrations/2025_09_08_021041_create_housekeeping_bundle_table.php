<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // limpiezas
        Schema::create('limpiezas', function (Blueprint $table) {
            $table->bigIncrements('id_limpieza');
            $table->string('nombre', 60);
            $table->date('fecha_inicio');
            $table->date('fecha_final')->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->dateTime('fecha_reporte');
            $table->string('notas', 250)->nullable();
            $table->string('prioridad', 60)->nullable();
            $table->unsignedBigInteger('id_usuario_asigna')->nullable();
            $table->unsignedBigInteger('id_usuario_reporta')->nullable();
            $table->unsignedBigInteger('id_habitacion')->nullable();
            $table->timestamps();

            $table->foreign('id_usuario_asigna')->references('id_usuario')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_usuario_reporta')->references('id_usuario')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_habitacion')->references('id_habitacion')->on('habitaciones')->nullOnDelete()->cascadeOnUpdate();
        });

        // historial_limpiezas
        Schema::create('historial_limpiezas', function (Blueprint $table) {
            $table->bigIncrements('id_historial_limp');
            $table->unsignedBigInteger('id_limpieza')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('evento', 100);
            $table->dateTime('fecha');
            $table->string('valor_anterior', 100)->nullable();
            $table->string('valor_nuevo', 100)->nullable();
            $table->timestamps();

            $table->foreign('id_limpieza')->references('id_limpieza')->on('limpiezas')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('actor_id')->references('id_usuario')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });

        // mantenimientos
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->bigIncrements('id_mantenimiento');
            $table->string('nombre', 50)->unique();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_final')->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->dateTime('fecha_reporte');
            $table->string('notas', 250)->nullable();
            $table->string('prioridad', 50)->nullable();
            $table->unsignedBigInteger('id_usuario_asigna')->nullable();
            $table->unsignedBigInteger('id_usuario_reporta')->nullable();
            $table->unsignedBigInteger('id_habitacion')->nullable();
            $table->timestamps();

            $table->foreign('id_usuario_asigna')->references('id_usuario')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_usuario_reporta')->references('id_usuario')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_habitacion')->references('id_habitacion')->on('habitaciones')->nullOnDelete()->cascadeOnUpdate();
        });

        // historial_mantenimientos
        Schema::create('historial_mantenimientos', function (Blueprint $table) {
            $table->bigIncrements('id_historial_mant');
            $table->unsignedBigInteger('id_mantenimiento')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('evento', 300);
            $table->dateTime('fecha');
            $table->string('valor_anterior', 300)->nullable();
            $table->string('valor_nuevo', 300)->nullable();
            $table->timestamps();

            $table->foreign('id_mantenimiento')->references('id_mantenimiento')->on('mantenimientos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('actor_id')->references('id_usuario')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void {
        Schema::dropIfExists('historial_mantenimientos');
        Schema::dropIfExists('mantenimientos');
        Schema::dropIfExists('historial_limpiezas');
        Schema::dropIfExists('limpiezas');
    }
};
