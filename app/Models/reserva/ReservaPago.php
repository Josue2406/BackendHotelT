<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

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
		'id_moneda' => 'int',
		'monto' => 'float',
		'tipo_cambio' => 'float',
		'monto_usd' => 'float',
		'fecha_pago' => 'datetime',
		'creado_por' => 'int'
	];

	protected $fillable = [
		'id_reserva',
		'id_metodo_pago',
		'id_tipo_transaccion',
		'id_estado_pago',
		'id_moneda',
		'monto',
		'tipo_cambio',
		'monto_usd',
		'referencia',
		'notas',
		'fecha_pago',
		'creado_por'
	];

	public function creado_por()
	{
		return $this->belongsTo(User::class, 'creado_por');
	}

	public function id_estado_pago()
	{
		return $this->belongsTo(\App\Models\catalago_pago\EstadoPago::class, 'id_estado_pago');
	}

	public function id_metodo_pago()
	{
		return $this->belongsTo(\App\Models\catalago_pago\MetodoPago::class, 'id_metodo_pago');
	}

	public function id_reserva()
	{
		return $this->belongsTo(\App\Models\reserva\Reserva::class, 'id_reserva');
	}

	public function id_tipo_transaccion()
	{
		return $this->belongsTo(\App\Models\catalago_pago\TipoTransaccion::class, 'id_tipo_transaccion');
	}

	public function cargo_reservas_where_id_reserva_pago()
	{
		return $this->hasMany(CargoReserva::class, 'id_reserva_pago');
	}

	public function transaccion_pagos_where_id_reserva_pago()
	{
		return $this->hasMany(TransaccionPago::class, 'id_reserva_pago');
	}

	// Relaciones amigables
	public function reserva()
	{
		return $this->id_reserva();
	}

	public function metodoPago()
	{
		return $this->id_metodo_pago();
	}

	public function estadoPago()
	{
		return $this->id_estado_pago();
	}

	public function moneda()
	{
		return $this->belongsTo(\App\Models\catalago_pago\Moneda::class, 'id_moneda');
	}

	/**
	 * Accessor para obtener información formateada del pago
	 */
	public function getMontoFormateadoAttribute(): string
	{
		$simbolo = $this->moneda->simbolo ?? '$';
		return "{$simbolo}" . number_format($this->monto, 2);
	}

	/**
	 * Accessor para obtener el tipo de cambio aplicado de forma legible
	 */
	public function getTipoCambioFormateadoAttribute(): string
	{
		$codigoMoneda = $this->moneda->codigo ?? 'USD';
		return "1 USD = " . number_format($this->tipo_cambio, 6) . " {$codigoMoneda}";
	}

	/**
	 * Accessor para obtener resumen del pago
	 */
	public function getResumenAttribute(): string
	{
		$metodo = $this->metodoPago->nombre ?? 'N/A';
		$estado = $this->estadoPago->nombre ?? 'N/A';
		$monto = $this->monto_formateado;

		return "{$monto} - {$metodo} ({$estado})";
	}
}
