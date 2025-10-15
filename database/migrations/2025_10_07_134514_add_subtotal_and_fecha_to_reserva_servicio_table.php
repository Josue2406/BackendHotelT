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
        Schema::table('reserva_servicio', function (Blueprint $table) {
            $table->date('fecha_servicio')->nullable()->after('descripcion');
            $table->decimal('subtotal', 10, 2)->default(0)->after('fecha_servicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_servicio', function (Blueprint $table) {
            $table->dropColumn(['fecha_servicio', 'subtotal']);
        });
    }
};
