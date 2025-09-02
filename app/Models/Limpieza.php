<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Limpieza
 * 
 * @property int $id_limpieza
 * @property string $nombre
 * @property Carbon $fecha_inicio
 * @property Carbon|null $fecha_final
 * @property string|null $descripcion
 * @property Carbon $fecha_reporte
 * @property string|null $notas
 * @property string|null $prioridad
 * @property int|null $id_usuario_asigna
 * @property int|null $id_usuario_reporta
 * @property int|null $id_habitacion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|HistorialLimpieza[] $historial_limpiezas_where_id_limpieza
 *
 * @package App\Models
 */
class Limpieza extends Model
{
	protected $table = 'limpiezas';
	protected $primaryKey = 'id_limpieza';

	protected $casts = [
		'fecha_inicio' => 'datetime',
		'fecha_final' => 'datetime',
		'fecha_reporte' => 'datetime',
		'id_usuario_asigna' => 'int',
		'id_usuario_reporta' => 'int',
		'id_habitacion' => 'int'
	];

	protected $fillable = [
		'nombre',
		'fecha_inicio',
		'fecha_final',
		'descripcion',
		'fecha_reporte',
		'notas',
		'prioridad',
		'id_usuario_asigna',
		'id_usuario_reporta',
		'id_habitacion'
	];

	public function id_habitacion()
	{
		return $this->belongsTo(Habitacione::class, 'id_habitacion');
	}

	public function id_usuario_asigna()
	{
		return $this->belongsTo(User::class, 'id_usuario_asigna');
	}

	public function id_usuario_reporta()
	{
		return $this->belongsTo(User::class, 'id_usuario_reporta');
	}

	public function historial_limpiezas_where_id_limpieza()
	{
		return $this->hasMany(HistorialLimpieza::class, 'id_limpieza');
	}
}
