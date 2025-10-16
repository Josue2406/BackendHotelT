<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\catalago_pago;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EstadoPago
 * 
 * @property int $id_estado_pago
 * @property string $nombre
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|ReservaPago[] $reserva_pagos_where_id_estado_pago
 *
 * @package App\Models
 */
class EstadoPago extends Model
{
	protected $table = 'estado_pago';
	protected $primaryKey = 'id_estado_pago';

	protected $fillable = [
		'nombre'
	];

	// Constantes para estados de pago
	public const ESTADO_PENDIENTE = 1;
	public const ESTADO_COMPLETADO = 2;
	public const ESTADO_FALLIDO = 3;
	public const ESTADO_REEMBOLSADO = 4;
	public const ESTADO_PARCIAL = 5;

	// Nombres de estados
	public const ESTADOS_VALIDOS = [
		self::ESTADO_PENDIENTE => 'Pendiente',
		self::ESTADO_COMPLETADO => 'Completado',
		self::ESTADO_FALLIDO => 'Fallido',
		self::ESTADO_REEMBOLSADO => 'Reembolsado',
		self::ESTADO_PARCIAL => 'Parcial',
	];

	public function reserva_pagos_where_id_estado_pago()
	{
		return $this->hasMany(ReservaPago::class, 'id_estado_pago');
	}
}
