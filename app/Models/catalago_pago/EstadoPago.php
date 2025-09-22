<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\catalago_pago;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\reserva\ReservaPago;
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

	public function reserva_pagos_where_id_estado_pago()
	{
		return $this->hasMany(ReservaPago::class, 'id_estado_pago');
	}
}
