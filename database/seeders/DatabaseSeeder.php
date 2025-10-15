<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🌱 Iniciando seeders del sistema hotelero...');
        $this->command->info('================================================');

        // 1. Catálogos generales (tipos, estados, fuentes)
        $this->call([
            CatalogosGeneralesSeeder::class,
        ]);

        // 2. Catálogos de pagos (monedas, métodos, estados)
        $this->call([
            CatalogosPagoSeeder::class,
        ]);

        // 3. Políticas de cancelación
        $this->call([
            PoliticaCancelacionSeeder::class,
        ]);

        // 4. Usuario de prueba (opcional)
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->command->info('');
        $this->command->info('================================================');
        $this->command->info('✅ SEEDERS COMPLETADOS EXITOSAMENTE');
        $this->command->info('================================================');
        $this->command->info('  Total de catálogos: ~60 registros');
        $this->command->info('  - Catálogos Generales: 40');
        $this->command->info('  - Catálogos de Pago: 35');
        $this->command->info('  - Políticas de Cancelación: 4');
        $this->command->info('================================================');
        $this->command->info('');
    }
}
