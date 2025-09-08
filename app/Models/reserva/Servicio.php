<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Servicio
 * 
 * @property int $id_servicio
 * @property string $nombre
 * @property float $precio
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Reserva[] $reservas
 *
 * @package App\Models
 */
class Servicio extends Model
{
	protected $table = 'servicio';
	protected $primaryKey = 'id_servicio';

	protected $casts = [
		'precio' => 'float'
	];

	protected $fillable = [
		'nombre',
		'precio',
		'descripcion'
	];

	public function reservas()
	{
		return $this->belongsToMany(Reserva::class, 'reserva_servicio', 'id_servicio', 'id_reserva')
					->withPivot('id_reserva_serv', 'cantidad', 'precio_unitario', 'descripcion')
					->withTimestamps();
	}
}
