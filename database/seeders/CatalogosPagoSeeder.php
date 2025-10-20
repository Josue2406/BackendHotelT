<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CatalogosPagoSeeder extends Seeder
{
    /**
     * Seed de todos los catálogos relacionados con pagos
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. ESTADOS DE PAGO
        DB::table('estado_pago')->insertOrIgnore([
            [
                'id_estado_pago' => 1,
                'nombre' => 'Pendiente',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_pago' => 2,
                'nombre' => 'Completado',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_pago' => 3,
                'nombre' => 'Fallido',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_pago' => 4,
                'nombre' => 'Reembolsado',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_estado_pago' => 5,
                'nombre' => 'Parcial',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->command->info('✅ Estados de pago insertados correctamente');

        // 2. TIPOS DE TRANSACCIÓN
        DB::table('tipo_transaccion')->insertOrIgnore([
            [
                'id_tipo_transaccion' => 1,
                'nombre' => 'Pago',
                'descripcion' => 'Pago de reserva o servicios',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_transaccion' => 2,
                'nombre' => 'Reembolso',
                'descripcion' => 'Devolución de dinero al cliente',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_transaccion' => 3,
                'nombre' => 'Cancelación',
                'descripcion' => 'Cancelación de pago',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_tipo_transaccion' => 4,
                'nombre' => 'Ajuste',
                'descripcion' => 'Ajuste manual de pago',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->command->info('✅ Tipos de transacción insertados correctamente');

        // 3. MONEDAS (las 16 soportadas por el sistema)
        DB::table('moneda')->insertOrIgnore([
            [
                'id_moneda' => 1,
                'codigo' => 'USD',
                'nombre' => 'Dólar Estadounidense',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 2,
                'codigo' => 'CRC',
                'nombre' => 'Colón Costarricense',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 3,
                'codigo' => 'EUR',
                'nombre' => 'Euro',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 4,
                'codigo' => 'GBP',
                'nombre' => 'Libra Esterlina',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 5,
                'codigo' => 'CAD',
                'nombre' => 'Dólar Canadiense',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 6,
                'codigo' => 'MXN',
                'nombre' => 'Peso Mexicano',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 7,
                'codigo' => 'JPY',
                'nombre' => 'Yen Japonés',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 8,
                'codigo' => 'CNY',
                'nombre' => 'Yuan Chino',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 9,
                'codigo' => 'BRL',
                'nombre' => 'Real Brasileño',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 10,
                'codigo' => 'ARS',
                'nombre' => 'Peso Argentino',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 11,
                'codigo' => 'COP',
                'nombre' => 'Peso Colombiano',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 12,
                'codigo' => 'CLP',
                'nombre' => 'Peso Chileno',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 13,
                'codigo' => 'PEN',
                'nombre' => 'Sol Peruano',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 14,
                'codigo' => 'CHF',
                'nombre' => 'Franco Suizo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 15,
                'codigo' => 'AUD',
                'nombre' => 'Dólar Australiano',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_moneda' => 16,
                'codigo' => 'NZD',
                'nombre' => 'Dólar Neozelandés',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->command->info('✅ Monedas insertadas correctamente (16 monedas)');

        // 4. MÉTODOS DE PAGO
        // NOTA: Ya NO incluyen id_moneda, porque la moneda va en el pago
        DB::table('metodo_pago')->insertOrIgnore([
            [
                'id_metodo_pago' => 1,
                'nombre' => 'Tarjeta de Crédito',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_metodo_pago' => 2,
                'nombre' => 'Tarjeta de Débito',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_metodo_pago' => 3,
                'nombre' => 'Efectivo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_metodo_pago' => 4,
                'nombre' => 'Transferencia Bancaria',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_metodo_pago' => 5,
                'nombre' => 'PayPal',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_metodo_pago' => 6,
                'nombre' => 'SINPE Móvil',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_metodo_pago' => 7,
                'nombre' => 'Depósito Bancario',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_metodo_pago' => 8,
                'nombre' => 'Stripe',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_metodo_pago' => 9,
                'nombre' => 'Mercado Pago',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_metodo_pago' => 10,
                'nombre' => 'Cheque',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->command->info('✅ Métodos de pago insertados correctamente (10 métodos)');

        // Resumen final
        $this->command->info('');
        $this->command->info('================================================');
        $this->command->info('✅ TODOS LOS CATÁLOGOS DE PAGO INSERTADOS');
        $this->command->info('================================================');
        $this->command->info('  - Estados de Pago: 5');
        $this->command->info('  - Tipos de Transacción: 4');
        $this->command->info('  - Monedas: 16');
        $this->command->info('  - Métodos de Pago: 10');
        $this->command->info('================================================');
    }
}
