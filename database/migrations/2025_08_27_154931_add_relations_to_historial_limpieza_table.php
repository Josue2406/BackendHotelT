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
        Schema::table('historial_limpiezas', function (Blueprint $table) {
             // Agregar columnas
            $table->unsignedBigInteger('id_limpieza')->after('id_historial_limp')->nullable();
            $table->unsignedBigInteger('actor_id')->after('id_limpieza')->nullable();

            // Índices 
            $table->index('id_limpieza', 'idx_hist_limp_limpieza');
            $table->index('actor_id', 'idx_hist_limp_actor');

            // FKs
            $table->foreign('id_limpieza', 'fk_hist_limp_limpieza')
                ->references('id_limpieza')->on('limpiezas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('actor_id', 'fk_hist_limp_actor')
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
        Schema::table('historial_limpiezas', function (Blueprint $table) {
            $table->dropForeign('fk_hist_limp_limpieza');
            $table->dropForeign('fk_hist_limp_actor');

            $table->dropIndex('idx_hist_limp_limpieza');
            $table->dropIndex('idx_hist_limp_actor');

            $table->dropColumn(['id_limpieza', 'actor_id']);
        });
    }
};
