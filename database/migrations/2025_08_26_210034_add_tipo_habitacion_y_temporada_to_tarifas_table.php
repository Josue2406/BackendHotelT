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
        Schema::table('tarifas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo_habitacion')->after('id')->nullable();
            $table->unsignedBigInteger('id_temporada')->after('id')->nullable();

            // Única por combinación (opcional pero recomendado)
            $table->unique(['id_tipo_habitacion', 'id_temporada'], 'uq_tarifa_tipo_temporada');

            // FK a tipos_habitacion (ajusta PK si es 'id')
            $table->foreign('id_tipo_habitacion', 'fk_tarifa_tipo_hab')
                ->references('id')->on('tipos_habitacion')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // FK a temporadas (ajusta PK si es 'id')
            $table->foreign('id_temporada', 'fk_tarifa_temporada')
                ->references('id_temporada')->on('temporadas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropForeign('fk_tarifa_tipo_hab');
            $table->dropForeign('fk_tarifa_temporada');
            $table->dropUnique('uq_tarifa_tipo_temporada');
            $table->dropColumn(['id_tipo_habitacion', 'id_temporada']);
        });
    }
};
