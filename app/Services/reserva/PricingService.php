<?php

namespace App\Services\reserva;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\habitacion\Habitacione;

// ⬇️ IMPORTA tus modelos reales de temporada/regla
use App\Models\reserva\Temporada;
// Si tu TemporadaRegla está en App\Models\reserva:
use App\Models\reserva\TemporadaRegla;
// Si la dejaste en App\Models, usa esta en lugar de la anterior:
// use App\Models\TemporadaRegla;

class PricingService
{
    /**
     * Calcula el precio para 1 noche en una fecha.
     * Retorna: ['base'=>float,'final'=>float,'regla'=>TemporadaRegla|null,'temporada'=>Temporada|null]
     */
    public function precioNoche(Habitacione $hab, Carbon $fecha, ?int $minNochesContext = null): array
    {
        $base = (float) $hab->precio_base;
        [$regla, $temporada] = $this->reglaAplicable($hab, $fecha, $minNochesContext);

        if (!$regla) {
            return ['base' => $base, 'final' => $base, 'regla' => null, 'temporada' => null];
        }

        $final = match ($regla->tipo_ajuste) {
            'PORCENTAJE' => $base * (1 + ((float)$regla->valor / 100)),
            'MONTO'      => $base + (float)$regla->valor,
        };

        return ['base' => $base, 'final' => round($final, 2), 'regla' => $regla, 'temporada' => $temporada];
    }

    /**
     * Calcula el precio total para [checkin, checkout).
     * Retorna: ['noches','base_total','final_total','detalle'=>[...]]
     */
    public function precioRango(Habitacione $hab, Carbon $checkin, Carbon $checkout): array
    {
        $period = CarbonPeriod::create($checkin, $checkout->copy()->subDay());
        $detalle = [];
        $baseTotal = 0.0;
        $finalTotal = 0.0;

        $noches = iterator_count($period);
        $minNochesContext = $noches;

        foreach ($period as $date) {
            $calc = $this->precioNoche($hab, Carbon::parse($date), $minNochesContext);
            $baseTotal += $calc['base'];
            $finalTotal += $calc['final'];
            $detalle[] = [
                'fecha'     => $date->toDateString(),
                'base'      => $calc['base'],
                'final'     => $calc['final'],
                'regla'     => $calc['regla']?->only(['id_regla','scope','tipo_ajuste','valor','prioridad']),
                'temporada' => $calc['temporada']?->only(['id_temporada','nombre']),
            ];
        }

        return [
            'noches'      => $noches,
            'base_total'  => round($baseTotal, 2),
            'final_total' => round($finalTotal, 2),
            'detalle'     => $detalle,
        ];
    }

    /** Selecciona la regla más específica/prioritaria para la fecha. */
    protected function reglaAplicable(Habitacione $hab, Carbon $fecha, ?int $minNochesContext = null): array
    {
        $temporadas = Temporada::query()
            ->where('activo', 1)
            ->whereDate('fecha_ini', '<=', $fecha->toDateString())
            ->whereDate('fecha_fin', '>=', $fecha->toDateString())
            ->get();

        if ($temporadas->isEmpty()) return [null, null];

        $dow = (string) $fecha->isoWeekday(); // 1..7

        $reglas = TemporadaRegla::query()
            ->whereIn('id_temporada', $temporadas->pluck('id_temporada'))
            ->where(function ($q) use ($hab) {
                $q->where('scope', 'HOTEL')
                  ->orWhere(fn($w) => $w->where('scope','TIPO')->where('tipo_habitacion_id', $hab->tipo_habitacion_id))
                  ->orWhere(fn($w) => $w->where('scope','HABITACION')->where('habitacion_id', $hab->id_habitacion));
            })
            ->get()
            ->filter(function ($r) use ($dow, $minNochesContext) {
                if (!is_null($r->aplica_dow) && $r->aplica_dow !== '') {
                    $dias = explode(',', $r->aplica_dow);
                    if (!in_array($dow, $dias, true)) return false;
                }
                if (!is_null($r->min_noches) && !is_null($minNochesContext) && $minNochesContext < $r->min_noches) {
                    return false;
                }
                return true;
            });

        if ($reglas->isEmpty()) return [null, null];

        $reglasOrdenadas = $reglas->sortByDesc(function ($r) use ($temporadas) {
            $especificidad = match ($r->scope) {
                'HABITACION' => 3,
                'TIPO'       => 2,
                default      => 1,
            };
            $prioTemp = optional($temporadas->firstWhere('id_temporada', $r->id_temporada))->prioridad ?? 0;
            return [$especificidad, $r->prioridad, $prioTemp];
        })->values();

        $ganadora = $reglasOrdenadas->first();
        $temp = $temporadas->firstWhere('id_temporada', $ganadora->id_temporada);

        return [$ganadora, $temp];
    }
}
