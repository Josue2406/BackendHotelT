<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\reserva\PoliticaCancelacion;

class PoliticaCancelacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $politicas = [
            [
                'id_politica' => PoliticaCancelacion::POLITICA_MAS_30_DIAS,
                'nombre' => 'Cancelación +30 días',
                'regla_ventana' => 'mas_de_30_dias',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 0.00, // 0% penalidad = 100% reembolso
                'descripcion' => 'Reembolso del 100% si cancela con más de 30 días de anticipación',
            ],
            [
                'id_politica' => PoliticaCancelacion::POLITICA_15_30_DIAS,
                'nombre' => 'Cancelación 15-30 días',
                'regla_ventana' => '15_a_30_dias',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 50.00, // 50% penalidad = 50% reembolso
                'descripcion' => 'Reembolso del 50% si cancela entre 15 y 30 días de anticipación',
            ],
            [
                'id_politica' => PoliticaCancelacion::POLITICA_7_14_DIAS,
                'nombre' => 'Cancelación 7-14 días',
                'regla_ventana' => '7_a_14_dias',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 75.00, // 75% penalidad = 25% reembolso
                'descripcion' => 'Reembolso del 25% si cancela entre 7 y 14 días de anticipación',
            ],
            [
                'id_politica' => PoliticaCancelacion::POLITICA_MENOS_7_DIAS,
                'nombre' => 'Cancelación -7 días',
                'regla_ventana' => 'menos_de_7_dias',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 100.00, // 100% penalidad = Sin reembolso
                'descripcion' => 'Sin reembolso si cancela con menos de 7 días de anticipación',
            ],
            [
                'id_politica' => PoliticaCancelacion::POLITICA_NO_SHOW,
                'nombre' => 'No-Show',
                'regla_ventana' => 'no_presentacion',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 100.00, // 100% penalidad = Sin reembolso
                'descripcion' => 'Sin reembolso por no presentación. Se cobra el 100% de la reserva',
            ],
        ];

        foreach ($politicas as $politica) {
            DB::table('politica_cancelacion')->updateOrInsert(
                ['id_politica' => $politica['id_politica']],
                $politica
            );
        }

        $this->command->info('Políticas de cancelación creadas exitosamente!');
    }
}