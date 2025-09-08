<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Tarifa
 * 
 * @property int $id_tarifa
 * @property int|null $id_tipo_habitacion
 * @property int|null $id_temporada
 * @property float $precio
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class Tarifa extends Model
{
	protected $table = 'tarifas';
	protected $primaryKey = 'id_tarifa';

	protected $casts = [
		'id_tipo_habitacion' => 'int',
		'id_temporada' => 'int',
		'precio' => 'float'
	];

	protected $fillable = [
		'id_tipo_habitacion',
		'id_temporada',
		'precio'
	];

	public function id_temporada()
	{
		return $this->belongsTo(Temporada::class, 'id_temporada');
	}

	public function id_tipo_habitacion()
	{
		return $this->belongsTo(TiposHabitacion::class, 'id_tipo_habitacion');
	}
}
