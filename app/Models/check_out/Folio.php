<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Folio
 * 
 * @property int $id_folio
 * @property int|null $id_reserva_hab
 * @property int|null $id_estadia
 * @property int $id_estado_folio
 * @property float $total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Factura[] $facturas_where_id_folio
 * @property Collection|NuevaEntradaFolio[] $nueva_entrada_folios_where_id_folio
 * @property Collection|TransaccionPago[] $transaccion_pagos_where_id_folio
 *
 * @package App\Models
 */
class Folio extends Model
{
	protected $table = 'folio';
	protected $primaryKey = 'id_folio';

	protected $casts = [
		'id_reserva_hab' => 'int',
		'id_estadia' => 'int',
		'id_estado_folio' => 'int',
		'total' => 'float'
	];

	protected $fillable = [
		'id_reserva_hab',
		'id_estadia',
		'id_estado_folio',
		'total'
	];

	public function id_estadia()
	{
		return $this->belongsTo(Estadium::class, 'id_estadia');
	}

	public function id_estado_folio()
	{
		return $this->belongsTo(EstadoFolio::class, 'id_estado_folio');
	}

	public function id_reserva_hab()
	{
		return $this->belongsTo(ReservaHabitacion::class, 'id_reserva_hab');
	}

	public function facturas_where_id_folio()
	{
		return $this->hasMany(Factura::class, 'id_folio');
	}

	public function nueva_entrada_folios_where_id_folio()
	{
		return $this->hasMany(NuevaEntradaFolio::class, 'id_folio');
	}

	public function transaccion_pagos_where_id_folio()
	{
		return $this->hasMany(TransaccionPago::class, 'id_folio');
	}
}
