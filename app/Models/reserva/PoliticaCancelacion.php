<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PoliticaCancelacion
 * 
 * @property int $id_politica
 * @property string $nombre
 * @property string $regla_ventana
 * @property string $penalidad_tipo
 * @property float $penalidad_valor
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|ReservaPolitica[] $reserva_politicas_where_id_politica
 *
 * @package App\Models
 */
class PoliticaCancelacion extends Model
{
	protected $table = 'politica_cancelacion';
	protected $primaryKey = 'id_politica';

	protected $casts = [
		'penalidad_valor' => 'float'
	];

	protected $fillable = [
		'nombre',
		'regla_ventana',
		'penalidad_tipo',
		'penalidad_valor',
		'descripcion'
	];

	// Tipos de penalidad
	public const TIPO_PORCENTAJE = 'porcentaje';
	public const TIPO_NOCHES = 'noches';
	public const TIPO_FIJO = 'fijo';

	// Políticas predefinidas (IDs)
	public const POLITICA_MAS_30_DIAS = 1;      // 100% reembolso
	public const POLITICA_15_30_DIAS = 2;       // 50% reembolso
	public const POLITICA_7_14_DIAS = 3;        // 25% reembolso
	public const POLITICA_MENOS_7_DIAS = 4;     // Sin reembolso
	public const POLITICA_NO_SHOW = 5;          // Sin reembolso + cargo completo

	/**
	 * Calcular el reembolso según la política y los días de anticipación
	 *
	 * @param float $montoPagado Monto total pagado
	 * @param int $diasAnticipacion Días entre cancelación y llegada
	 * @return array ['reembolso' => float, 'penalidad' => float, 'politica' => PoliticaCancelacion]
	 */
	public static function calcularReembolso(float $montoPagado, int $diasAnticipacion): array
	{
		// Determinar política aplicable según días de anticipación
		$politica = self::obtenerPoliticaPorDias($diasAnticipacion);

		if (!$politica) {
			return [
				'reembolso' => 0,
				'penalidad' => $montoPagado,
				'politica' => null,
				'mensaje' => 'No se encontró política aplicable'
			];
		}

		// Calcular según tipo de penalidad
		$reembolso = 0;
		$penalidad = 0;

		switch ($politica->penalidad_tipo) {
			case self::TIPO_PORCENTAJE:
				// penalidad_valor es el % de penalidad (ej: 0 = 100% reembolso, 50 = 50% reembolso, 100 = sin reembolso)
				$porcentajeReembolso = 100 - $politica->penalidad_valor;
				$reembolso = $montoPagado * ($porcentajeReembolso / 100);
				$penalidad = $montoPagado - $reembolso;
				break;

			case self::TIPO_FIJO:
				$penalidad = min($politica->penalidad_valor, $montoPagado);
				$reembolso = $montoPagado - $penalidad;
				break;

			case self::TIPO_NOCHES:
				// Para implementación futura si se necesita
				$penalidad = 0;
				$reembolso = $montoPagado;
				break;
		}

		return [
			'reembolso' => round($reembolso, 2),
			'penalidad' => round($penalidad, 2),
			'politica' => $politica,
			'mensaje' => $politica->descripcion ?? $politica->nombre
		];
	}

	/**
	 * Obtener política aplicable según días de anticipación
	 */
	public static function obtenerPoliticaPorDias(int $diasAnticipacion): ?self
	{
		if ($diasAnticipacion > 30) {
			return self::find(self::POLITICA_MAS_30_DIAS);
		} elseif ($diasAnticipacion >= 15) {
			return self::find(self::POLITICA_15_30_DIAS);
		} elseif ($diasAnticipacion >= 7) {
			return self::find(self::POLITICA_7_14_DIAS);
		} else {
			return self::find(self::POLITICA_MENOS_7_DIAS);
		}
	}

	/**
	 * Obtener política para No-Show
	 */
	public static function obtenerPoliticaNoShow(): ?self
	{
		return self::find(self::POLITICA_NO_SHOW);
	}

	public function reserva_politicas_where_id_politica()
	{
		return $this->hasMany(ReservaPolitica::class, 'id_politica');
	}

	// Alias
	public function reservaPoliticas()
	{
		return $this->reserva_politicas_where_id_politica();
	}
}
