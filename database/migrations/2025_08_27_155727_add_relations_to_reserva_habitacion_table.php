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
        Schema::table('reserva_habitacions', function (Blueprint $table) {
            // columnas nuevas
            $table->unsignedBigInteger('id_reserva')->after('id_reserva_hab')->nullable();
            $table->unsignedBigInteger('id_habitacion')->after('id_reserva')->nullable();

            // índices
            $table->index('id_reserva', 'idx_reserva_hab_reserva');
            $table->index('id_habitacion', 'idx_reserva_hab_habitacion');

            // FKs
            $table->foreign('id_reserva', 'fk_reserva_hab_reserva')
                ->references('id_reserva')->on('reserva')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('id_habitacion', 'fk_reserva_hab_habitacion')
                ->references('id')->on('habitaciones')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_habitacions', function (Blueprint $table) {
            $table->dropForeign('fk_reserva_hab_reserva');
            $table->dropForeign('fk_reserva_hab_habitacion');

            $table->dropIndex('idx_reserva_hab_reserva');
            $table->dropIndex('idx_reserva_hab_habitacion');

            $table->dropColumn(['id_reserva', 'id_habitacion']);
        });
    }
};
