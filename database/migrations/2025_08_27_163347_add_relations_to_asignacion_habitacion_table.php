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
        Schema::table('asignacion_habitacions', function (Blueprint $table) {
            // columnas nuevas
            $table->unsignedBigInteger('id_hab')->after('id_asignacion')->nullable();
            $table->unsignedBigInteger('id_reserva')->after('id_hab')->nullable();

            // índices
            $table->index('id_hab', 'idx_asig_hab_hab');
            $table->index('id_reserva', 'idx_asig_hab_reserva');

            // FKs
            $table->foreign('id_hab', 'fk_asig_hab_hab')
                ->references('id')->on('habitaciones')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('id_reserva', 'fk_asig_hab_reserva')
                ->references('id_reserva')->on('reserva')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignacion_habitacions', function (Blueprint $table) {
            $table->dropForeign('fk_asig_hab_hab');
            $table->dropForeign('fk_asig_hab_reserva');

            $table->dropIndex('idx_asig_hab_hab');
            $table->dropIndex('idx_asig_hab_reserva');

            $table->dropColumn(['id_hab', 'id_reserva']);
        });
    }
};
