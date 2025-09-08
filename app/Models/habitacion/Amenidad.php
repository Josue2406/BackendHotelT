<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Amenidad
 * 
 * @property int $id_amenidad
 * @property string $nombre
 * @property string $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|HabitacionAmenidad[] $habitacion_amenidads_where_id_amenidad
 *
 * @package App\Models
 */
class Amenidad extends Model
{
	protected $table = 'amenidads';
	protected $primaryKey = 'id_amenidad';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function habitacion_amenidads_where_id_amenidad()
	{
		return $this->hasMany(HabitacionAmenidad::class, 'id_amenidad');
	}
}
