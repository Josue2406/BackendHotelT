<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reserva', function (Blueprint $table) {
            // Agregar campos para tracking de pagos
            $table->decimal('monto_pagado', 10, 2)->default(0)->after('total_monto_reserva');
            $table->decimal('monto_pendiente', 10, 2)->default(0)->after('monto_pagado');
            $table->decimal('porcentaje_minimo_pago', 5, 2)->default(30.00)->after('monto_pendiente'); // % mínimo para confirmar
            $table->boolean('pago_completo')->default(false)->after('porcentaje_minimo_pago');
        });

        // Agregar constraint para asegurar que monto_pagado no exceda el total
        DB::statement('
            ALTER TABLE reserva
            ADD CONSTRAINT chk_monto_pagado_valido
            CHECK (monto_pagado >= 0 AND monto_pagado <= total_monto_reserva)
        ');

        // Agregar constraint para que porcentaje sea válido
        DB::statement('
            ALTER TABLE reserva
            ADD CONSTRAINT chk_porcentaje_minimo_valido
            CHECK (porcentaje_minimo_pago >= 0 AND porcentaje_minimo_pago <= 100)
        ');

        // Agregar notas al pago en la tabla reserva_pago
        Schema::table('reserva_pago', function (Blueprint $table) {
            $table->string('notas', 300)->nullable()->after('fecha_pago');
            $table->string('referencia_transaccion', 100)->nullable()->after('notas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar constraints primero
        DB::statement('ALTER TABLE reserva DROP CONSTRAINT IF EXISTS chk_monto_pagado_valido');
        DB::statement('ALTER TABLE reserva DROP CONSTRAINT IF EXISTS chk_porcentaje_minimo_valido');

        Schema::table('reserva', function (Blueprint $table) {
            $table->dropColumn(['monto_pagado', 'monto_pendiente', 'porcentaje_minimo_pago', 'pago_completo']);
        });

        Schema::table('reserva_pago', function (Blueprint $table) {
            $table->dropColumn(['notas', 'referencia_transaccion']);
        });
    }
};