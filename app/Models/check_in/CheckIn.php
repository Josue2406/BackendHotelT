<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\check_in;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\check_in\AsignacionHabitacion;

/**
 * Class CheckIn
 * 
 * @property int $id_checkin
 * @property int $id_asignacion
 * @property Carbon $fecha_hora
 * @property string|null $obervacion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class CheckIn extends Model
{
	protected $table = 'check_ins';
	protected $primaryKey = 'id_checkin';

	protected $casts = [
		'id_asignacion' => 'int',
		'fecha_hora' => 'datetime'
	];

	protected $fillable = [
		'id_asignacion',
		'fecha_hora',
		'obervacion'
	];


	  /** Relación inversa: muchos checkins pertenecen a una asignación */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionHabitacion::class, 'id_asignacion', 'id_asignacion');
    }

    /** Scopes útiles */
    public function scopeRecientes($query)
    {
        return $query->orderByDesc('fecha_hora');
    }
	/*
	public function id_asignacion()
	{
		return $this->belongsTo(AsignacionHabitacion::class, 'id_asignacion');
	}*/
}
