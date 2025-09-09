<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\check_out;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CheckOut
 * 
 * @property int $id_checkout
 * @property int|null $id_asignacion
 * @property Carbon $fecha_hora
 * @property string $resultado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class CheckOut extends Model
{
	protected $table = 'check_outs';
	protected $primaryKey = 'id_checkout';

	protected $casts = [
		'id_asignacion' => 'int',
		'fecha_hora' => 'datetime'
	];

	protected $fillable = [
		'id_asignacion',
		'fecha_hora',
		'resultado'
	];

	public function id_asignacion()
	{
		return $this->belongsTo(AsignacionHabitacion::class, 'id_asignacion');
	}
}
