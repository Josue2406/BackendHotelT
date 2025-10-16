<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CatalogosGeneralesSeeder extends Seeder
{
    /**
     * Seed de TODOS los catálogos generales del sistema hotelero
     */
    public function run(): void
    {
        $now = Carbon::now();

        // ==========================================
        // 1. TIPOS DE DOCUMENTO (Clientes)
        // ==========================================
        DB::table('tipo_doc')->insertOrIgnore([
            ['id_tipo_doc' => 1, 'nombre' => 'Cédula Nacional'],
            ['id_tipo_doc' => 2, 'nombre' => 'Pasaporte'],
            ['id_tipo_doc' => 3, 'nombre' => 'DIMEX (Extranjero)'],
            ['id_tipo_doc' => 4, 'nombre' => 'Cédula Jurídica'],
            ['id_tipo_doc' => 5, 'nombre' => 'Licencia de Conducir'],
        ]);
        $this->command->info('✅ Tipos de Documento insertados (5)');

        // ==========================================
        // 2. FUENTES DE RESERVA (Origen/Canal)
        // ==========================================
        DB::table('fuentes')->insertOrIgnore([
            [
                'id_fuente' => 1,
                'nombre' => 'Sitio Web Oficial',
                'codigo' => 'WEB',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_fuente' => 2,
                'nombre' => 'Recepción (Walk-in)',
                'codigo' => 'WALKIN',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_fuente' => 3,
                'nombre' => 'Booking.com',
                'codigo' => 'BOOKING',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_fuente' => 4,
                'nombre' => 'Airbnb',
                'codigo' => 'AIRBNB',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_fuente' => 5,
                'nombre' => 'Expedia',
                'codigo' => 'EXPEDIA',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_fuente' => 6,
                'nombre' => 'Agencia de Viajes',
                'codigo' => 'AGENCY',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_fuente' => 7,
                'nombre' => 'Teléfono',
                'codigo' => 'PHONE',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_fuente' => 8,
                'nombre' => 'Email',
                'codigo' => 'EMAIL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_fuente' => 9,
                'nombre' => 'Redes Sociales',
                'codigo' => 'SOCIAL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_fuente' => 10,
                'nombre' => 'Referido',
                'codigo' => 'REFERRAL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $this->command->info('✅ Fuentes de Reserva insertadas (10)');

        // ==========================================
        // 3. TIPOS DE HABITACIÓN
        // ==========================================
        DB::table('tipos_habitacion')->insertOrIgnore([
            [
                'id_tipo_hab' => 1,
                'nombre' => 'Individual',
                'descripcion' => 'Habitación para una persona con cama individual',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_hab' => 2,
                'nombre' => 'Doble',
                'descripcion' => 'Habitación para dos personas con cama matrimonial o dos camas individuales',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_hab' => 3,
                'nombre' => 'Triple',
                'descripcion' => 'Habitación para tres personas',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_hab' => 4,
                'nombre' => 'Suite',
                'descripcion' => 'Habitación amplia con sala de estar separada',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_hab' => 5,
                'nombre' => 'Suite Presidencial',
                'descripcion' => 'Suite de lujo con múltiples habitaciones y servicios premium',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_hab' => 6,
                'nombre' => 'Familiar',
                'descripcion' => 'Habitación grande para familias con capacidad para 4-6 personas',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_hab' => 7,
                'nombre' => 'Ejecutiva',
                'descripcion' => 'Habitación con espacio de trabajo y servicios de negocios',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_hab' => 8,
                'nombre' => 'Deluxe',
                'descripcion' => 'Habitación de categoría superior con amenidades mejoradas',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $this->command->info('✅ Tipos de Habitación insertados (8)');

        // ==========================================
        // 4. ESTADOS DE HABITACIÓN
        // ==========================================
        DB::table('estado_habitacions')->insertOrIgnore([
            [
                'id_estado_hab' => 1,
                'nombre' => 'Disponible',
                'descripcion' => 'Habitación lista para ser ocupada',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_hab' => 2,
                'nombre' => 'Ocupada',
                'descripcion' => 'Habitación actualmente en uso por un huésped',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_hab' => 3,
                'nombre' => 'Sucia',
                'descripcion' => 'Habitación que requiere limpieza',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_hab' => 4,
                'nombre' => 'Limpia',
                'descripcion' => 'Habitación limpia pero aún no disponible',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_hab' => 5,
                'nombre' => 'Mantenimiento',
                'descripcion' => 'Habitación fuera de servicio por mantenimiento',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $this->command->info('✅ Estados de Habitación insertados (5)');

        // ==========================================
        // 5. ESTADOS DE RESERVA
        // ==========================================
        DB::table('estado_reserva')->insertOrIgnore([
            [
                'id_estado_res' => 1,
                'nombre' => 'Pendiente',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_res' => 2,
                'nombre' => 'Cancelada',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_res' => 3,
                'nombre' => 'Confirmada',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_res' => 4,
                'nombre' => 'Check-in',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_res' => 5,
                'nombre' => 'Check-out',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_res' => 6,
                'nombre' => 'No Show',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_res' => 7,
                'nombre' => 'En Espera',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_res' => 8,
                'nombre' => 'Finalizada',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $this->command->info('✅ Estados de Reserva insertados (8)');

        // ==========================================
        // 6. ESTADOS DE ESTADÍA
        // ==========================================
        DB::table('estado_estadia')->insertOrIgnore([
            [
                'id_estado_estadia' => 1,
                'nombre' => 'Activa',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_estadia' => 2,
                'nombre' => 'Finalizada',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_estadia' => 3,
                'nombre' => 'Cancelada',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_estadia' => 4,
                'nombre' => 'En Proceso de Check-out',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $this->command->info('✅ Estados de Estadía insertados (4)');

        // ==========================================
        // RESUMEN FINAL
        // ==========================================
        $this->command->info('');
        $this->command->info('================================================');
        $this->command->info('✅ TODOS LOS CATÁLOGOS GENERALES INSERTADOS');
        $this->command->info('================================================');
        $this->command->info('  - Tipos de Documento: 5');
        $this->command->info('  - Fuentes de Reserva: 10');
        $this->command->info('  - Tipos de Habitación: 8');
        $this->command->info('  - Estados de Habitación: 5');
        $this->command->info('  - Estados de Reserva: 8');
        $this->command->info('  - Estados de Estadía: 4');
        $this->command->info('================================================');
        $this->command->info('  TOTAL: 40 registros de catálogo');
        $this->command->info('================================================');
    }
}
