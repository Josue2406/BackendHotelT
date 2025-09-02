<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TiposHabitacion
 * 
 * @property int $id
 * @property string $nombre
 * @property string $codigo
 * @property array|null $amenidades
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Collection|Habitacione[] $habitaciones_where_tipo_habitacion
 *
 * @package App\Models
 */
class TiposHabitacion extends Model
{
	use SoftDeletes;
	protected $table = 'tipos_habitacion';

	protected $casts = [
		'amenidades' => 'json'
	];

	protected $fillable = [
		'nombre',
		'codigo',
		'amenidades',
		'descripcion'
	];

	public function habitaciones_where_tipo_habitacion()
	{
		return $this->hasMany(Habitacione::class, 'tipo_habitacion_id');
	}
}
