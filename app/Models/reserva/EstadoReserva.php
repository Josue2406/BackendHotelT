<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EstadoReserva
 * 
 * @property int $id_estado_res
 * @property string $nombre
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Reserva[] $reservas_where_id_estado_re
 *
 * @package App\Models
 */
class EstadoReserva extends Model
{
	protected $table = 'estado_reserva';
	protected $primaryKey = 'id_estado_res';

	protected $fillable = [
		'nombre'
	];

	public function reservas_where_id_estado_re()
	{
		return $this->hasMany(Reserva::class, 'id_estado_res');
	}
}
