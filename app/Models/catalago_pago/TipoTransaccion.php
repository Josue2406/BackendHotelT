<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\catalago_pago;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TipoTransaccion
 * 
 * @property int $id_tipo_transaccion
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|ReservaPago[] $reserva_pagos_where_id_tipo_transaccion
 * @property Collection|TransaccionPago[] $transaccion_pagos_where_id_tipo_transaccion
 *
 * @package App\Models
 */
class TipoTransaccion extends Model
{
	protected $table = 'tipo_transaccion';
	protected $primaryKey = 'id_tipo_transaccion';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function reserva_pagos_where_id_tipo_transaccion()
	{
		return $this->hasMany(ReservaPago::class, 'id_tipo_transaccion');
	}

	public function transaccion_pagos_where_id_tipo_transaccion()
	{
		return $this->hasMany(TransaccionPago::class, 'id_tipo_transaccion');
	}
}
