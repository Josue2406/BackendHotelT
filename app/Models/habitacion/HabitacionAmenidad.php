<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HabitacionAmenidad
 * 
 * @property int $id_amenidad_habitacion
 * @property int $id_habitacion
 * @property int $id_amenidad
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class HabitacionAmenidad extends Model
{
	protected $table = 'habitacion_amenidads';
	protected $primaryKey = 'id_amenidad_habitacion';

	protected $casts = [
		'id_habitacion' => 'int',
		'id_amenidad' => 'int'
	];

	protected $fillable = [
		'id_habitacion',
		'id_amenidad'
	];

	public function id_amenidad()
	{
		return $this->belongsTo(Amenidad::class, 'id_amenidad');
	}

	public function id_habitacion()
	{
		return $this->belongsTo(Habitacione::class, 'id_habitacion');
	}
}
