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
        Schema::table('habitacion_amenidads', function (Blueprint $table) {
            $table->unsignedBigInteger('id_habitacion')->after('id_amenidad_habitacion')->nullable();
            $table->unsignedBigInteger('id_amenidad')->after('id_habitacion')->nullable();

            // 2) (Opcional, pero recomendado) Índice único para evitar duplicados
            $table->unique(['id_habitacion', 'id_amenidad'], 'uq_hab_amenidad');

            // 3) Agregar claves foráneas
            $table->foreign('id_habitacion', 'fk_hab_amenidad_hab')
                ->references('id')->on('habitaciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('id_amenidad', 'fk_hab_amenidad_amenidad')
                ->references('id_amenidad')->on('amenidads')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('habitacion_amenidads', function (Blueprint $table) {
            $table->dropForeign('fk_hab_amenidad_hab');
            $table->dropForeign('fk_hab_amenidad_amenidad');
            $table->dropUnique('uq_hab_amenidad');

            // Luego soltar columnas
            $table->dropColumn(['id_habitacion', 'id_amenidad']);
        });
    }
};
