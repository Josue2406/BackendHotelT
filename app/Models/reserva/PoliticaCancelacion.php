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

	// Políticas del Hotel Lanaku (IDs)
	public const POLITICA_ESTANDAR = 1;              // Cancelación 72+ horas: sin cargo
	public const POLITICA_NO_REEMBOLSABLE = 2;      // Tarifas especiales: sin reembolso
	public const POLITICA_NO_SHOW = 3;              // No-Show: cargo total
	public const POLITICA_TEMPORADA_ALTA = 4;       // Temporada alta: 15 días anticipación
	public const POLITICA_FUERZA_MAYOR = 5;         // Casos especiales: evaluación individual

	// Políticas antiguas (compatibilidad)
	public const POLITICA_MAS_30_DIAS = 1;
	public const POLITICA_15_30_DIAS = 2;
	public const POLITICA_7_14_DIAS = 3;
	public const POLITICA_MENOS_7_DIAS = 4;

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
	 * Obtener política aplicable según días de anticipación (Hotel Lanaku)
	 */
	public static function obtenerPoliticaPorDias(int $diasAnticipacion, bool $esTemporadaAlta = false): ?self
	{
		// Si es temporada alta o evento especial, requiere 15 días
		if ($esTemporadaAlta) {
			if ($diasAnticipacion >= 15) {
				return self::find(self::POLITICA_TEMPORADA_ALTA); // Sin cargo si cancela 15+ días antes
			} else {
				return self::find(self::POLITICA_TEMPORADA_ALTA); // Cobra 100% primera noche
			}
		}

		// Política estándar: 72 horas (3 días)
		$horasAnticipacion = $diasAnticipacion * 24;

		if ($horasAnticipacion >= 72) {
			return self::find(self::POLITICA_ESTANDAR); // Sin cargo
		} else {
			return self::find(self::POLITICA_ESTANDAR); // Cobra primera noche con impuestos
		}
	}

	/**
	 * Obtener política para No-Show
	 */
	public static function obtenerPoliticaNoShow(): ?self
	{
		return self::find(self::POLITICA_NO_SHOW);
	}

	/**
	 * Obtener política para tarifas no reembolsables
	 */
	public static function obtenerPoliticaNoReembolsable(): ?self
	{
		return self::find(self::POLITICA_NO_REEMBOLSABLE);
	}

	/**
	 * Obtener política para casos de fuerza mayor
	 */
	public static function obtenerPoliticaFuerzaMayor(): ?self
	{
		return self::find(self::POLITICA_FUERZA_MAYOR);
	}

	/**
	 * Calcular reembolso según política Hotel Lanaku
	 *
	 * @param float $montoPagado
	 * @param int $diasAnticipacion
	 * @param bool $esTemporadaAlta
	 * @param bool $esTarifaNoReembolsable
	 * @return array
	 */
	public static function calcularReembolsoHotelLanaku(
		float $montoPagado,
		int $diasAnticipacion,
		bool $esTemporadaAlta = false,
		bool $esTarifaNoReembolsable = false
	): array {
		// Tarifas no reembolsables
		if ($esTarifaNoReembolsable) {
			$politica = self::obtenerPoliticaNoReembolsable();
			return [
				'reembolso' => 0,
				'penalidad' => $montoPagado,
				'politica' => $politica,
				'mensaje' => 'Tarifa no reembolsable: no aplican reembolsos ni modificaciones'
			];
		}

		// Temporada Alta o Eventos Especiales
		if ($esTemporadaAlta) {
			$politica = self::find(self::POLITICA_TEMPORADA_ALTA);

			if ($diasAnticipacion >= 15) {
				// Sin cargo
				return [
					'reembolso' => $montoPagado,
					'penalidad' => 0,
					'politica' => $politica,
					'mensaje' => 'Cancelación sin cargo (15+ días de anticipación)'
				];
			} else {
				// Cobra 100% de la primera noche
				// Asumimos que montoPagado puede ser mayor que una noche
				// Por simplicidad, cobramos un porcentaje (ej: 30% como primera noche)
				$penalidad = $montoPagado * 0.30; // Primera noche aproximada
				$reembolso = $montoPagado - $penalidad;

				return [
					'reembolso' => round($reembolso, 2),
					'penalidad' => round($penalidad, 2),
					'politica' => $politica,
					'mensaje' => 'Temporada alta: se cobra 100% de la primera noche'
				];
			}
		}

		// Política Estándar: 72 horas
		$politica = self::find(self::POLITICA_ESTANDAR);
		$horasAnticipacion = $diasAnticipacion * 24;

		if ($horasAnticipacion >= 72) {
			// Sin cargo
			return [
				'reembolso' => $montoPagado,
				'penalidad' => 0,
				'politica' => $politica,
				'mensaje' => 'Cancelación sin cargo (72+ horas de anticipación)'
			];
		} else {
			// Cobra primera noche con impuestos (aproximadamente 30%)
			$penalidad = $montoPagado * 0.30;
			$reembolso = $montoPagado - $penalidad;

			return [
				'reembolso' => round($reembolso, 2),
				'penalidad' => round($penalidad, 2),
				'politica' => $politica,
				'mensaje' => 'Se cobra la primera noche con impuestos (cancelación con menos de 72 horas)'
			];
		}
	}

	/**
	 * Calcular penalidad para No-Show
	 */
	public static function calcularPenalidadNoShow(float $montoTotal): array
	{
		$politica = self::obtenerPoliticaNoShow();

		return [
			'reembolso' => 0,
			'penalidad' => $montoTotal,
			'politica' => $politica,
			'mensaje' => 'No-Show: se cobra el total de la estancia reservada'
		];
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
