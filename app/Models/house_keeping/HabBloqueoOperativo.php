<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\house_keeping;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HabBloqueoOperativo
 * 
 * @property int $id_bloqueo
 * @property int $id_habitacion
 * @property string $tipo
 * @property string|null $motivo
 * @property Carbon $fecha_ini
 * @property Carbon $fecha_fin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class HabBloqueoOperativo extends Model
{
	protected $table = 'hab_bloqueo_operativo';
	protected $primaryKey = 'id_bloqueo';

	protected $casts = [
		'id_habitacion' => 'int',
		'fecha_ini' => 'datetime',
		'fecha_fin' => 'datetime'
	];

	protected $fillable = [
		'id_habitacion',
		'tipo',
		'motivo',
		'fecha_ini',
		'fecha_fin'
	];

	public function id_habitacion()
	{
		return $this->belongsTo(Habitacione::class, 'id_habitacion');
	}
}
