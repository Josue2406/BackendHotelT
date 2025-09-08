<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TiposHabitacion
 * 
 * @property int $id_tipo_hab
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Habitacione[] $habitaciones_where_tipo_habitacion_id
 * @property Collection|Tarifa[] $tarifas_where_id_tipo_habitacion
 *
 * @package App\Models
 */
class TiposHabitacion extends Model
{
	protected $table = 'tipos_habitacion';
	protected $primaryKey = 'id_tipo_hab';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function habitaciones_where_tipo_habitacion_id()
	{
		return $this->hasMany(Habitacione::class, 'tipo_habitacion_id');
	}

	public function tarifas_where_id_tipo_habitacion()
	{
		return $this->hasMany(Tarifa::class, 'id_tipo_habitacion');
	}
}
