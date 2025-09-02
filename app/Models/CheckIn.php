<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CheckIn
 * 
 * @property int $id_checkin
 * @property Carbon $fecha_hora
 * @property string $obervacion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $id_asignacion
 * 
 *
 * @package App\Models
 */
class CheckIn extends Model
{
	protected $table = 'check_ins';
	protected $primaryKey = 'id_checkin';

	protected $casts = [
		'fecha_hora' => 'datetime',
		'id_asignacion' => 'int'
	];

	protected $fillable = [
		'fecha_hora',
		'obervacion',
		'id_asignacion'
	];

	public function id_asignacion()
	{
		return $this->belongsTo(AsignacionHabitacion::class, 'id_asignacion');
	}
}
