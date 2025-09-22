<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\usuario\User;
use App\Models\catalago_pago\EstadoPago;
use App\Models\catalago_pago\MetodoPago;
use App\Models\catalago_pago\TipoTransaccion;
use App\Models\reserva\Reserva;
use App\Models\reserva\CargoReserva;
use App\Models\catalago_pago\TransaccionPago;
/**
 * Class ReservaPago
 * 
 * @property int $id_reserva_pago
 * @property int $id_reserva
 * @property int|null $id_metodo_pago
 * @property int|null $id_tipo_transaccion
 * @property int|null $id_estado_pago
 * @property float $monto
 * @property Carbon $fecha_pago
 * @property int $creado_por
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|CargoReserva[] $cargo_reservas_where_id_reserva_pago
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
		'id_metodo_pago' => 'int',
		'id_tipo_transaccion' => 'int',
		'id_estado_pago' => 'int',
		'monto' => 'float',
		'fecha_pago' => 'datetime',
		'creado_por' => 'int'
	];

	protected $fillable = [
		'id_reserva',
		'id_metodo_pago',
		'id_tipo_transaccion',
		'id_estado_pago',
		'monto',
		'fecha_pago',
		'creado_por'
	];

	public function creado_por()
	{
		return $this->belongsTo(User::class, 'creado_por');
	}

	public function id_estado_pago()
	{
		return $this->belongsTo(EstadoPago::class, 'id_estado_pago');
	}

	public function id_metodo_pago()
	{
		return $this->belongsTo(MetodoPago::class, 'id_metodo_pago');
	}

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}

	public function id_tipo_transaccion()
	{
		return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
	}

	public function cargo_reservas_where_id_reserva_pago()
	{
		return $this->hasMany(CargoReserva::class, 'id_reserva_pago');
	}

	public function transaccion_pagos_where_id_reserva_pago()
	{
		return $this->hasMany(TransaccionPago::class, 'id_reserva_pago');
	}
}
