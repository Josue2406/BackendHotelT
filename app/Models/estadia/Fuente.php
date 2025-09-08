<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Fuente
 * 
 * @property int $id_fuente
 * @property string $nombre
 * @property string $codigo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Collection|Estadium[] $estadia_where_id_fuente
 * @property Collection|Reserva[] $reservas_where_id_fuente
 *
 * @package App\Models
 */
class Fuente extends Model
{
	use SoftDeletes;
	protected $table = 'fuentes';
	protected $primaryKey = 'id_fuente';

	protected $fillable = [
		'nombre',
		'codigo'
	];

	public function estadia_where_id_fuente()
	{
		return $this->hasMany(Estadium::class, 'id_fuente');
	}

	public function reservas_where_id_fuente()
	{
		return $this->hasMany(Reserva::class, 'id_fuente');
	}
}
