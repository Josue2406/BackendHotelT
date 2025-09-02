<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ReservaPago
 * 
 * @property int $id_reserva_pago
 * @property int $id_reserva
 * @property string $tipo
 * @property float $monto
 * @property string $estado
 * @property Carbon $fecha_pago
 * @property int $creado_por
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|TransaccionPago[] $transaccion_pagos_where_id_reserva_pago
 *
 * @package App\Models
 */
class ReservaPago extends Model
{
	protected $table = 'reserva_pago';
	protected $primaryKey = 'id_reserva_pago';

	protected $casts = [
		'id_reserva' => 'int',
		'monto' => 'float',
		'fecha_pago' => 'datetime',
		'creado_por' => 'int'
	];

	protected $fillable = [
		'id_reserva',
		'tipo',
		'monto',
		'estado',
		'fecha_pago',
		'creado_por'
	];

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}

	public function transaccion_pagos_where_id_reserva_pago()
	{
		return $this->hasMany(TransaccionPago::class, 'id_reserva_pago');
	}
}
