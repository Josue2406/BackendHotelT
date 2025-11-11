<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\reserva\PoliticaCancelacion;

class PoliticaCancelacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Políticas de Cancelación del Hotel Lanaku
     */
    public function run(): void
    {
        // Eliminar registros con nombres duplicados que queremos reemplazar
        DB::table('politica_cancelacion')->whereIn('nombre', [
            'No-Show',
            'Política Estándar',
            'Tarifas No Reembolsables',
            'Temporada Alta / Eventos',
            'Fuerza Mayor'
        ])->delete();

        $politicas = [
            // 1. POLÍTICA ESTÁNDAR - 72 horas
            [
                'id_politica' => PoliticaCancelacion::POLITICA_ESTANDAR,
                'nombre' => 'Política Estándar',
                'regla_ventana' => '72_horas',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 0.00, // Sin cargo si cancela 72+ horas antes
                'descripcion' => 'Sin cargo 72+ hrs antes. Menos de 72 hrs: primera noche con impuestos.',
            ],

            // 2. TARIFAS NO REEMBOLSABLES
            [
                'id_politica' => PoliticaCancelacion::POLITICA_NO_REEMBOLSABLE,
                'nombre' => 'Tarifas No Reembolsables',
                'regla_ventana' => 'no_reembolsable',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 100.00, // Sin reembolso
                'descripcion' => 'Pago total al reservar. Sin reembolsos ni modificaciones.',
            ],

            // 3. NO-SHOW (No presentación)
            [
                'id_politica' => PoliticaCancelacion::POLITICA_NO_SHOW,
                'nombre' => 'No-Show',
                'regla_ventana' => 'no_presentacion',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 100.00, // Cargo total
                'descripcion' => 'Sin presentación: se cobra el total de la estancia sin reembolso.',
            ],

            // 4. TEMPORADA ALTA O EVENTOS ESPECIALES
            [
                'id_politica' => PoliticaCancelacion::POLITICA_TEMPORADA_ALTA,
                'nombre' => 'Temporada Alta / Eventos',
                'regla_ventana' => '15_dias',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 30.00, // Cobra primera noche (aprox 30%)
                'descripcion' => 'Sin cargo 15+ días antes. Menos de 15 días: 100% primera noche.',
            ],

            // 5. CASOS DE FUERZA MAYOR
            [
                'id_politica' => PoliticaCancelacion::POLITICA_FUERZA_MAYOR,
                'nombre' => 'Fuerza Mayor',
                'regla_ventana' => 'evaluacion_individual',
                'penalidad_tipo' => PoliticaCancelacion::TIPO_PORCENTAJE,
                'penalidad_valor' => 0.00, // Variable según caso
                'descripcion' => 'Evaluación individual. Cambio de fecha o crédito según caso.',
            ],
        ];

        foreach ($politicas as $politica) {
            // Primero eliminar si existe con ese ID pero diferente nombre
            DB::table('politica_cancelacion')
                ->where('id_politica', $politica['id_politica'])
                ->where('nombre', '!=', $politica['nombre'])
                ->delete();

            // Luego insertar o actualizar
            DB::table('politica_cancelacion')->updateOrInsert(
                ['id_politica' => $politica['id_politica']],
                $politica
            );

            $this->command->info("✓ Política '{$politica['nombre']}' creada/actualizada.");
        }

        $this->command->info("\n✅ Políticas de cancelación del Hotel Lanaku creadas exitosamente!");
        $this->command->info("Total de políticas: 5");
    }
}