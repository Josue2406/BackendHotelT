<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\house_keeping;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\usuario\User;
use App\Models\house_keeping\Mantenimiento;
/**
 * Class HistorialMantenimiento
 * 
 * @property int $id_historial_mant
 * @property int|null $id_mantenimiento
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
class HistorialMantenimiento extends Model
{
	protected $table = 'historial_mantenimientos';
	protected $primaryKey = 'id_historial_mant';

	protected $casts = [
		'id_mantenimiento' => 'int',
		'actor_id' => 'int',
		'fecha' => 'datetime'
	];

	protected $fillable = [
		'id_mantenimiento',
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

    // Relación con el mantenimiento
    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'id_mantenimiento', 'id_mantenimiento');
    }
}