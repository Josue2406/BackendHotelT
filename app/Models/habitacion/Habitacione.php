<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\habitacion;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\habitacion\EstadoHabitacion;
use App\Models\habitacion\TiposHabitacion;
use App\Models\house_keeping\HabBloqueoOperativo;
use App\Models\house_keeping\Limpieza;
use App\Models\house_keeping\Mantenimiento;

/**
 * Class Habitacione
 * 
 * @property int $id_habitacion
 * @property int $id_estado_hab
 * @property int $tipo_habitacion_id
 * @property string $nombre
 * @property string $numero
 * @property int $piso
 * @property int $capacidad
 * @property string $medida
 * @property string $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Collection|AsignacionHabitacion[] $asignacion_habitacions_where_id_hab
 * @property Collection|HabBloqueoOperativo[] $hab_bloqueo_operativos_where_id_habitacion
 * @property Collection|HabitacionAmenidad[] $habitacion_amenidads_where_id_habitacion
 * @property Collection|Limpieza[] $limpiezas_where_id_habitacion
 * @property Collection|Mantenimiento[] $mantenimientos_where_id_habitacion
 * @property Collection|ReservaHabitacion[] $reserva_habitacions_where_id_habitacion
 *
 * @package App\Models
 */
class Habitacione extends Model
{
	use SoftDeletes;
	protected $table = 'habitaciones';
	protected $primaryKey = 'id_habitacion';

	protected $casts = [
		'id_estado_hab' => 'int',
		'tipo_habitacion_id' => 'int',
		'piso' => 'int',
		'capacidad' => 'int'
	];

	protected $fillable = [
		'id_estado_hab',
		'tipo_habitacion_id',
		'nombre',
		'numero',
		'piso',
		'capacidad',
		'medida',
		'descripcion'
	];

	public function id_estado_hab()
	{
		return $this->belongsTo(EstadoHabitacion::class, 'id_estado_hab');
	}

	public function tipo_habitacion_id()
	{
		return $this->belongsTo(TiposHabitacion::class, 'tipo_habitacion_id');
	}

	public function asignacion_habitacions_where_id_hab()
	{
		return $this->hasMany(AsignacionHabitacion::class, 'id_hab');
	}

	public function bloqueo_operativos()
	{
		return $this->hasMany(HabBloqueoOperativo::class, 'id_habitacion', 'id_habitacion');
	}

	public function habitacion_amenidads_where_id_habitacion()
	{
		return $this->hasMany(HabitacionAmenidad::class, 'id_habitacion');
	}

	public function limpiezas_where_id_habitacion()
	{
		return $this->hasMany(Limpieza::class, 'id_habitacion');
	}

	public function mantenimientos_where_id_habitacion()
	{
		return $this->hasMany(Mantenimiento::class, 'id_habitacion');
	}

	public function reserva_habitacions_where_id_habitacion()
	{
		return $this->hasMany(ReservaHabitacion::class, 'id_habitacion');
	}

	// Alias legibles para usar en with() y load()
public function estado()
{
    return $this->id_estado_hab();
}

public function tipo()
{
    return $this->tipo_habitacion_id();
}

}
