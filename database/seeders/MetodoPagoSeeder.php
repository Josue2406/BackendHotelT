<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\catalago_pago\MetodoPago;
use App\Models\catalago_pago\Moneda;
use Illuminate\Support\Facades\DB;

class MetodoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Inserta los 5 métodos de pago del Hotel Lanaku
     */
    public function run(): void
    {
        // Obtener ID de la moneda USD (moneda base del sistema)
        $monedaUSD = Moneda::where('codigo', 'USD')->first();

        if (!$monedaUSD) {
            $this->command->error('No se encontró la moneda USD en la base de datos. Por favor, ejecute el seeder de monedas primero.');
            return;
        }

        // Limpiar registros temporales existentes (códigos Z0, Z1, etc.)
        DB::table('metodo_pago')->whereIn('codigo', ['Z0', 'Z1', 'XX'])->delete();

        $metodosPago = [
            [
                'codigo' => MetodoPago::EFECTIVO, // 'CA'
                'nombre' => 'Efectivo',
                'descripcion' => 'Pago en efectivo (Cash). Puede realizarse en cualquier divisa aceptada por el hotel (USD, CRC, EUR).',
                'id_moneda' => $monedaUSD->id_moneda,
                'activo' => true,
                'requiere_autorizacion' => false,
            ],
            [
                'codigo' => MetodoPago::VISA_MASTERCARD, // 'VI'
                'nombre' => 'Visa / Mastercard',
                'descripcion' => 'Pago con tarjeta de crédito o débito Visa o Mastercard.',
                'id_moneda' => $monedaUSD->id_moneda,
                'activo' => true,
                'requiere_autorizacion' => false,
            ],
            [
                'codigo' => MetodoPago::AMERICAN_EXPRESS, // 'AX'
                'nombre' => 'American Express',
                'descripcion' => 'Pago con tarjeta American Express.',
                'id_moneda' => $monedaUSD->id_moneda,
                'activo' => true,
                'requiere_autorizacion' => false,
            ],
            [
                'codigo' => MetodoPago::TRANSFERENCIA_BANCARIA, // 'TB'
                'nombre' => 'Transferencia Bancaria',
                'descripcion' => 'Pago mediante depósito o transferencia bancaria nacional o internacional.',
                'id_moneda' => $monedaUSD->id_moneda,
                'activo' => true,
                'requiere_autorizacion' => false,
            ],
            [
                'codigo' => MetodoPago::CREDITO, // 'CR'
                'nombre' => 'Crédito',
                'descripcion' => 'Pago diferido a una fecha posterior al check-out. Aplica únicamente a clientes frecuentes VIP o agencias de viajes, según políticas y acuerdos del hotel.',
                'id_moneda' => $monedaUSD->id_moneda,
                'activo' => true,
                'requiere_autorizacion' => true, // Requiere autorización especial
            ],
        ];

        foreach ($metodosPago as $metodo) {
            // Usar updateOrCreate para evitar duplicados
            MetodoPago::updateOrCreate(
                ['codigo' => $metodo['codigo']], // Buscar por código
                $metodo // Actualizar o crear con estos datos
            );

            $this->command->info("✓ Método de pago '{$metodo['nombre']}' ({$metodo['codigo']}) creado/actualizado.");
        }

        $this->command->info("\n✅ Seeder de métodos de pago completado exitosamente.");
        $this->command->info("Total de métodos de pago: " . MetodoPago::count());
    }
}
