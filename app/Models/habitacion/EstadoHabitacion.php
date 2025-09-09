<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\habitacion;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EstadoHabitacion
 * 
 * @property int $id_estado_hab
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Habitacione[] $habitaciones_where_id_estado_hab
 *
 * @package App\Models
 */
class EstadoHabitacion extends Model
{
	protected $table = 'estado_habitacions';
	protected $primaryKey = 'id_estado_hab';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function habitaciones_where_id_estado_hab()
	{
		return $this->hasMany(Habitacione::class, 'id_estado_hab');
	}
}
