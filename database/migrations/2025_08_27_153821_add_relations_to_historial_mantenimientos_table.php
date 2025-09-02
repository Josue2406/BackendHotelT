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
        Schema::table('historial_mantenimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_mantenimiento')->after('id_historial_mant')->nullable();
            $table->unsignedBigInteger('actor_id')->after('id_mantenimiento')->nullable();

            // Índices
            $table->index('id_mantenimiento', 'idx_hist_mant_mantenimiento');
            $table->index('actor_id', 'idx_hist_mant_actor');

            // FK a mantenimientos
            $table->foreign('id_mantenimiento', 'fk_hist_mant_mantenimiento')
                ->references('id_mantenimiento')->on('mantenimientos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // FK a usuarios
            $table->foreign('actor_id', 'fk_hist_mant_actor')
                ->references('id_usuario')->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_mantenimientos', function (Blueprint $table) {
            $table->dropForeign('fk_hist_mant_mantenimiento');
            $table->dropForeign('fk_hist_mant_actor');

            $table->dropIndex('idx_hist_mant_mantenimiento');
            $table->dropIndex('idx_hist_mant_actor');

            $table->dropColumn(['id_mantenimiento', 'actor_id']);
        });
    }
};
