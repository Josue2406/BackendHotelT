<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HistorialLimpieza
 * 
 * @property int $id_historial_limp
 * @property int|null $id_limpieza
 * @property int|null $actor_id
 * @property string $evento
 * @property Carbon $fecha
 * @property string|null $valor_anterior
 * @property string|null $valor_nuevo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class HistorialLimpieza extends Model
{
	protected $table = 'historial_limpiezas';
	protected $primaryKey = 'id_historial_limp';

	protected $casts = [
		'id_limpieza' => 'int',
		'actor_id' => 'int',
		'fecha' => 'datetime'
	];

	protected $fillable = [
		'id_limpieza',
		'actor_id',
		'evento',
		'fecha',
		'valor_anterior',
		'valor_nuevo'
	];

	public function actor_id()
	{
		return $this->belongsTo(User::class, 'actor_id');
	}

	public function id_limpieza()
	{
		return $this->belongsTo(Limpieza::class, 'id_limpieza');
	}
}
