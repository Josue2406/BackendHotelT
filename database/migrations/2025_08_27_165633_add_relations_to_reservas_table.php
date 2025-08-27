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
        Schema::table('reserva', function (Blueprint $table) {
             // FK hacia clientes
            $table->foreign('id_cliente', 'fk_reserva_cliente')
                ->references('id_cliente')->on('clientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // FK hacia estados de reserva
            $table->foreign('id_estado_res', 'fk_reserva_estado')
                ->references('id_estado_res')->on('estado_reserva')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva', function (Blueprint $table) {
            $table->dropForeign('fk_reserva_cliente');
            $table->dropForeign('fk_reserva_estado');
        });
    }
};
