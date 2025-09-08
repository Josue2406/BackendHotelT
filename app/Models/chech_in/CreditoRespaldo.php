<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CreditoRespaldo
 * 
 * @property int $id_credito
 * @property int $id_reserva_hab
 * @property float $monto
 * @property int $id_estado_credito
 * @property Carbon $fecha
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|TransaccionPago[] $transaccion_pagos_where_id_credito
 *
 * @package App\Models
 */
class CreditoRespaldo extends Model
{
	protected $table = 'credito_respaldo';
	protected $primaryKey = 'id_credito';

	protected $casts = [
		'id_reserva_hab' => 'int',
		'monto' => 'float',
		'id_estado_credito' => 'int',
		'fecha' => 'datetime'
	];

	protected $fillable = [
		'id_reserva_hab',
		'monto',
		'id_estado_credito',
		'fecha'
	];

	public function id_estado_credito()
	{
		return $this->belongsTo(EstadoCredito::class, 'id_estado_credito');
	}

	public function id_reserva_hab()
	{
		return $this->belongsTo(ReservaHabitacion::class, 'id_reserva_hab');
	}

	public function transaccion_pagos_where_id_credito()
	{
		return $this->hasMany(TransaccionPago::class, 'id_credito');
	}
}
