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
            $table->unsignedBigInteger('id_reserva')->after('id_checkout')->nullable();

            // Índice
            $table->index('id_reserva', 'idx_checkout_reserva');

            // Relación con reservas
            $table->foreign('id_reserva', 'fk_checkout_reserva')
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
        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropForeign('fk_checkout_reserva');
            $table->dropIndex('idx_checkout_reserva');
            $table->dropColumn('id_reserva');
        });
    }
};
