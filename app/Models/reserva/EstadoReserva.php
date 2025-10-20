<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EstadoReserva
 *
 * @property int $id_estado_res
 * @property string $nombre
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Reserva[] $reservas_where_id_estado_re
 *
 * @package App\Models
 */
class EstadoReserva extends Model
{
	protected $table = 'estado_reserva';
	protected $primaryKey = 'id_estado_res';

	protected $fillable = [
		'nombre'
	];

	// Constantes para estados válidos (según tu base de datos)
	public const ESTADO_PENDIENTE = 1;
	public const ESTADO_CANCELADA = 2;
	public const ESTADO_CONFIRMADA = 3;
	public const ESTADO_CHECKIN = 4;
	public const ESTADO_CHECKOUT = 5;
	public const ESTADO_NO_SHOW = 6;
	public const ESTADO_EN_ESPERA = 7;
	public const ESTADO_FINALIZADA = 8;

	// Nombres de estados
	public const ESTADOS_VALIDOS = [
		self::ESTADO_PENDIENTE => 'Pendiente',
		self::ESTADO_CANCELADA => 'Cancelada',
		self::ESTADO_CONFIRMADA => 'Confirmada',
		self::ESTADO_CHECKIN => 'Check-in',
		self::ESTADO_CHECKOUT => 'Check-out',
		self::ESTADO_NO_SHOW => 'No show',
		self::ESTADO_EN_ESPERA => 'En espera',
		self::ESTADO_FINALIZADA => 'Finalizada',
	];

	/**
	 * Obtener todos los IDs de estados válidos
	 */
	public static function getEstadosValidosIds(): array
	{
		return array_keys(self::ESTADOS_VALIDOS);
	}

	/**
	 * Verificar si un ID de estado es válido
	 */
	public static function esEstadoValido(int $idEstado): bool
	{
		return array_key_exists($idEstado, self::ESTADOS_VALIDOS);
	}

	/**
	 * Obtener nombre del estado
	 */
	public static function getNombreEstado(int $idEstado): ?string
	{
		return self::ESTADOS_VALIDOS[$idEstado] ?? null;
	}

	/**
	 * Verificar si se puede cambiar de un estado a otro
	 */
	public static function puedeCambiarEstado(int $estadoActual, int $estadoNuevo): bool
	{
		// Reglas de transición de estados
		$transicionesPermitidas = [
			self::ESTADO_PENDIENTE => [self::ESTADO_CONFIRMADA, self::ESTADO_CANCELADA, self::ESTADO_EN_ESPERA],
			self::ESTADO_EN_ESPERA => [self::ESTADO_CONFIRMADA, self::ESTADO_CANCELADA],
			self::ESTADO_CONFIRMADA => [self::ESTADO_CANCELADA, self::ESTADO_CHECKIN, self::ESTADO_NO_SHOW],
			self::ESTADO_CHECKIN => [self::ESTADO_CHECKOUT, self::ESTADO_CANCELADA],
			self::ESTADO_CHECKOUT => [self::ESTADO_FINALIZADA],
			self::ESTADO_CANCELADA => [], // No se puede cambiar desde cancelada
			self::ESTADO_NO_SHOW => [], // No se puede cambiar desde no show
			self::ESTADO_FINALIZADA => [], // No se puede cambiar desde finalizada
		];

		return in_array($estadoNuevo, $transicionesPermitidas[$estadoActual] ?? []);
	}

	public function reservas_where_id_estado_re()
	{
		return $this->hasMany(Reserva::class, 'id_estado_res');
	}
}
