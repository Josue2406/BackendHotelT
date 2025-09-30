<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\house_keeping;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\usuario\User;
use App\Models\house_keeping\Limpieza;
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

	public function actor()
	{
		return $this->belongsTo(User::class, 'actor_id', 'id_usuario');
	}

	public function limpieza()
	{
		return $this->belongsTo(Limpieza::class, 'id_limpieza', 'id_limpieza');
	}
}
