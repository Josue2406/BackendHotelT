<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CargoReserva
 * 
 * @property int $id_cargo
 * @property int $id_reserva_pago
 * @property string $tipo_cargo
 * @property float $monto
 * @property Carbon $fecha
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|TransaccionPago[] $transaccion_pagos_where_id_cargo_reserva
 *
 * @package App\Models
 */
class CargoReserva extends Model
{
	protected $table = 'cargo_reserva';
	protected $primaryKey = 'id_cargo';

	protected $casts = [
		'id_reserva_pago' => 'int',
		'monto' => 'float',
		'fecha' => 'datetime'
	];

	protected $fillable = [
		'id_reserva_pago',
		'tipo_cargo',
		'monto',
		'fecha'
	];

	public function id_reserva_pago()
	{
		return $this->belongsTo(ReservaPago::class, 'id_reserva_pago');
	}

	public function transaccion_pagos_where_id_cargo_reserva()
	{
		return $this->hasMany(TransaccionPago::class, 'id_cargo_reserva');
	}
}
