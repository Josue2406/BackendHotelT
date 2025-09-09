<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\estadia;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Estadium
 * 
 * @property int $id_estadia
 * @property int|null $id_reserva
 * @property int $id_cliente_titular
 * @property int|null $id_fuente
 * @property Carbon $fecha_llegada
 * @property Carbon $fecha_salida
 * @property int $adultos
 * @property int $ninos
 * @property int $bebes
 * @property int|null $id_estado_estadia
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|AsignacionHabitacion[] $asignacion_habitacions_where_id_estadium
 * @property Collection|Folio[] $folios_where_id_estadium
 *
 * @package App\Models
 */
class Estadium extends Model
{
	protected $table = 'estadia';
	protected $primaryKey = 'id_estadia';

	protected $casts = [
		'id_reserva' => 'int',
		'id_cliente_titular' => 'int',
		'id_fuente' => 'int',
		'fecha_llegada' => 'datetime',
		'fecha_salida' => 'datetime',
		'adultos' => 'int',
		'ninos' => 'int',
		'bebes' => 'int',
		'id_estado_estadia' => 'int'
	];

	protected $fillable = [
		'id_reserva',
		'id_cliente_titular',
		'id_fuente',
		'fecha_llegada',
		'fecha_salida',
		'adultos',
		'ninos',
		'bebes',
		'id_estado_estadia'
	];

	public function id_cliente_titular()
	{
		return $this->belongsTo(Cliente::class, 'id_cliente_titular');
	}

	public function id_estado_estadia()
	{
		return $this->belongsTo(EstadoEstadium::class, 'id_estado_estadia');
	}

	public function id_fuente()
	{
		return $this->belongsTo(Fuente::class, 'id_fuente');
	}

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}

	public function asignacion_habitacions_where_id_estadium()
	{
		return $this->hasMany(AsignacionHabitacion::class, 'id_estadia');
	}

	public function folios_where_id_estadium()
	{
		return $this->hasMany(Folio::class, 'id_estadia');
	}
}
