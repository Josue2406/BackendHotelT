<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\catalago_pago;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MetodoPago
 * 
 * @property int $id_metodo_pago
 * @property int $id_moneda
 * @property string $nombre
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|ReservaPago[] $reserva_pagos_where_id_metodo_pago
 * @property Collection|TransaccionPago[] $transaccion_pagos_where_id_metodo_pago
 *
 * @package App\Models
 */
class MetodoPago extends Model
{
	protected $table = 'metodo_pago';
	protected $primaryKey = 'id_metodo_pago';

	protected $casts = [
		'id_moneda' => 'int'
	];

	protected $fillable = [
		'id_moneda',
		'nombre'
	];

	public function id_moneda()
	{
		return $this->belongsTo(Moneda::class, 'id_moneda');
	}

	public function reserva_pagos_where_id_metodo_pago()
	{
		return $this->hasMany(ReservaPago::class, 'id_metodo_pago');
	}

	public function transaccion_pagos_where_id_metodo_pago()
	{
		return $this->hasMany(TransaccionPago::class, 'id_metodo_pago');
	}
}
