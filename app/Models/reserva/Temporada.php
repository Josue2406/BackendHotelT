<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Temporada
 * 
 * @property int $id_temporada
 * @property string $campo
 * @property Carbon $fecha_ini
 * @property Carbon $fecha_fin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Tarifa[] $tarifas_where_id_temporada
 *
 * @package App\Models
 */
class Temporada extends Model
{
	protected $table = 'temporadas';
	protected $primaryKey = 'id_temporada';

	protected $casts = [
		'fecha_ini' => 'datetime',
		'fecha_fin' => 'datetime'
	];

	protected $fillable = [
		'campo',
		'fecha_ini',
		'fecha_fin'
	];

	public function tarifas_where_id_temporada()
	{
		return $this->hasMany(Tarifa::class, 'id_temporada');
	}
}
