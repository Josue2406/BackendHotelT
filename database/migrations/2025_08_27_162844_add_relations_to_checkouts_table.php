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
        Schema::table('check_outs', function (Blueprint $table) {
            $table->unsignedBigInteger('id_asignacion')->after('id_checkout')->nullable();

            // Índice
            $table->index('id_asignacion', 'idx_checkout_asignacion');

            // Relación con reservas
            $table->foreign('id_asignacion', 'fk_checkout_asignacion')
                ->references('id_asignacion')->on('asignacion_habitacions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropForeign('fk_checkout_asignacion');
            $table->dropIndex('idx_checkout_asignacion');
            $table->dropColumn('id_asignacion');
        });
    }
};
