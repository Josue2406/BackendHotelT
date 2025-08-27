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
        Schema::table('limpiezas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario_asigna')->after('prioridad')->nullable();
            $table->unsignedBigInteger('id_usuario_reporta')->after('id_usuario_asigna')->nullable();
            $table->unsignedBigInteger('id_habitacion')->after('id_usuario_reporta')->nullable();

            // índices
            $table->index('id_usuario_asigna', 'idx_limpieza_us_asigna');
            $table->index('id_usuario_reporta', 'idx_limpieza_us_reporta');
            $table->index('id_habitacion', 'idx_limpieza_habitacion');

            // FKs
            $table->foreign('id_usuario_asigna', 'fk_limpieza_us_asigna')
                ->references('id_usuario')->on('usuarios') 
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('id_usuario_reporta', 'fk_limpieza_us_reporta')
                ->references('id_usuario')->on('usuarios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('id_habitacion', 'fk_limpieza_habitacion')
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
        Schema::table('limpiezas', function (Blueprint $table) {
            $table->dropForeign('fk_limpieza_us_asigna');
            $table->dropForeign('fk_limpieza_us_reporta');
            $table->dropForeign('fk_limpieza_habitacion');

            $table->dropIndex('idx_limpieza_us_asigna');
            $table->dropIndex('idx_limpieza_us_reporta');
            $table->dropIndex('idx_limpieza_habitacion');

            $table->dropColumn(['id_usuario_asigna', 'id_usuario_reporta', 'id_habitacion']);
        });
    }
};
