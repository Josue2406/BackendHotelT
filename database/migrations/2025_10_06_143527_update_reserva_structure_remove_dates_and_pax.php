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
        // 1) Quitar fechas y pax de reserva (ahora están en reserva_habitacions)
        Schema::table('reserva', function (Blueprint $table) {
            $table->dropIndex('idx_reserva_fechas');
            $table->dropColumn(['fecha_llegada', 'fecha_salida', 'adultos', 'ninos', 'bebes']);
        });

        // 2) Agregar pax detallado y subtotal a reserva_habitacions
        Schema::table('reserva_habitacions', function (Blueprint $table) {
            // Quitar pax_total y agregar pax detallado
            $table->dropColumn('pax_total');

            $table->integer('adultos')->default(1)->after('fecha_salida');
            $table->integer('ninos')->default(0)->after('adultos');
            $table->integer('bebes')->default(0)->after('ninos');

            // Subtotal calculado para esta habitación
            $table->decimal('subtotal', 10, 2)->default(0)->after('bebes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva', function (Blueprint $table) {
            $table->date('fecha_llegada')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->integer('adultos')->default(1);
            $table->integer('ninos')->default(0);
            $table->integer('bebes')->default(0);
            $table->index(['fecha_llegada', 'fecha_salida'], 'idx_reserva_fechas');
        });

        Schema::table('reserva_habitacions', function (Blueprint $table) {
            $table->dropColumn(['adultos', 'ninos', 'bebes', 'subtotal']);
            $table->integer('pax_total')->default(1);
        });
    }
};
