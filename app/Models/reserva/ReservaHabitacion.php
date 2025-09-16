<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ReservaHabitacion
 * 
 * @property int $id_reserva_hab
 * @property int|null $id_reserva
 * @property int|null $id_habitacion
 * @property Carbon $fecha_llegada
 * @property Carbon $fecha_salida
 * @property int $pax_total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|CreditoRespaldo[] $credito_respaldos_where_id_reserva_hab
 * @property Collection|Folio[] $folios_where_id_reserva_hab
 *
 * @package App\Models
 */
class ReservaHabitacion extends Model
{
	protected $table = 'reserva_habitacions';
	protected $primaryKey = 'id_reserva_hab';

	protected $casts = [
		'id_reserva' => 'int',
		'id_habitacion' => 'int',
		'fecha_llegada' => 'datetime',
		'fecha_salida' => 'datetime',
		'pax_total' => 'int'
	];

	protected $fillable = [
		'id_reserva',
		'id_habitacion',
		'fecha_llegada',
		'fecha_salida',
		'pax_total'
	];

	/*
	public function hayChoqueHab(int $idHab, string $desde, string $hasta): bool
{
    // fin exclusivo: [llegada, salida)
    return ReservaHabitacion::query()
        ->where('id_habitacion', $idHab)
        ->where('fecha_llegada', '<', $hasta)
        ->where('fecha_salida',  '>', $desde)
        ->exists();
} */
	public function id_habitacion()
	{
		return $this->belongsTo(Habitacione::class, 'id_habitacion');
	}

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}

	public function credito_respaldos_where_id_reserva_hab()
	{
		return $this->hasMany(CreditoRespaldo::class, 'id_reserva_hab');
	}

	public function folios_where_id_reserva_hab()
	{
		return $this->hasMany(Folio::class, 'id_reserva_hab');
	}
}
