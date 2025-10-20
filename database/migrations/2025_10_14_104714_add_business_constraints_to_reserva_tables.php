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
        // 1. Constraint: fecha_salida debe ser mayor que fecha_llegada en reserva_habitacions
        DB::statement('
            ALTER TABLE reserva_habitacions
            ADD CONSTRAINT chk_fecha_salida_mayor_llegada
            CHECK (fecha_salida > fecha_llegada)
        ');

        // 2. Constraint: capacidad total (adultos + ninos + bebes) no debe exceder capacidad de la habitación
        // Este constraint requiere hacer un join, por lo que lo implementaremos a nivel de aplicación
        // Pero agregamos un constraint básico para asegurar que haya al menos 1 adulto
        DB::statement('
            ALTER TABLE reserva_habitacions
            ADD CONSTRAINT chk_adultos_minimo
            CHECK (adultos >= 1)
        ');

        DB::statement('
            ALTER TABLE reserva_habitacions
            ADD CONSTRAINT chk_ocupantes_no_negativos
            CHECK (adultos >= 0 AND ninos >= 0 AND bebes >= 0)
        ');

        // 3. Constraint: precios no pueden ser negativos en reserva
        DB::statement('
            ALTER TABLE reserva
            ADD CONSTRAINT chk_total_monto_no_negativo
            CHECK (total_monto_reserva >= 0)
        ');

        // 4. Constraint: subtotal no puede ser negativo en reserva_habitacions
        DB::statement('
            ALTER TABLE reserva_habitacions
            ADD CONSTRAINT chk_subtotal_no_negativo
            CHECK (subtotal >= 0)
        ');

        // 5. Constraint: precios en reserva_servicio deben ser válidos
        DB::statement('
            ALTER TABLE reserva_servicio
            ADD CONSTRAINT chk_precio_unitario_positivo
            CHECK (precio_unitario >= 0)
        ');

        DB::statement('
            ALTER TABLE reserva_servicio
            ADD CONSTRAINT chk_cantidad_positiva
            CHECK (cantidad > 0)
        ');

        DB::statement('
            ALTER TABLE reserva_servicio
            ADD CONSTRAINT chk_subtotal_servicio_no_negativo
            CHECK (subtotal >= 0)
        ');

        // 6. Constraint: precio en servicio debe ser no negativo
        DB::statement('
            ALTER TABLE servicio
            ADD CONSTRAINT chk_precio_servicio_no_negativo
            CHECK (precio >= 0)
        ');

        // 7. Constraint: precio_base en habitaciones debe ser no negativo
        DB::statement('
            ALTER TABLE habitaciones
            ADD CONSTRAINT chk_precio_base_no_negativo
            CHECK (precio_base IS NULL OR precio_base >= 0)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar constraints en orden inverso
        DB::statement('ALTER TABLE habitaciones DROP CONSTRAINT IF EXISTS chk_precio_base_no_negativo');
        DB::statement('ALTER TABLE servicio DROP CONSTRAINT IF EXISTS chk_precio_servicio_no_negativo');
        DB::statement('ALTER TABLE reserva_servicio DROP CONSTRAINT IF EXISTS chk_subtotal_servicio_no_negativo');
        DB::statement('ALTER TABLE reserva_servicio DROP CONSTRAINT IF EXISTS chk_cantidad_positiva');
        DB::statement('ALTER TABLE reserva_servicio DROP CONSTRAINT IF EXISTS chk_precio_unitario_positivo');
        DB::statement('ALTER TABLE reserva_habitacions DROP CONSTRAINT IF EXISTS chk_subtotal_no_negativo');
        DB::statement('ALTER TABLE reserva DROP CONSTRAINT IF EXISTS chk_total_monto_no_negativo');
        DB::statement('ALTER TABLE reserva_habitacions DROP CONSTRAINT IF EXISTS chk_ocupantes_no_negativos');
        DB::statement('ALTER TABLE reserva_habitacions DROP CONSTRAINT IF EXISTS chk_adultos_minimo');
        DB::statement('ALTER TABLE reserva_habitacions DROP CONSTRAINT IF EXISTS chk_fecha_salida_mayor_llegada');
    }
};