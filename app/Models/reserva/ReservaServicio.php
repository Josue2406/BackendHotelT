<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ReservaServicio
 * 
 * @property int $id_reserva_serv
 * @property int $id_reserva
 * @property int $id_servicio
 * @property int $cantidad
 * @property float $precio_unitario
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class ReservaServicio extends Model
{
	protected $table = 'reserva_servicio';
	protected $primaryKey = 'id_reserva_serv';

	protected $casts = [
		'id_reserva' => 'int',
		'id_servicio' => 'int',
		'cantidad' => 'int',
		'precio_unitario' => 'float'
	];

	protected $fillable = [
		'id_reserva',
		'id_servicio',
		'cantidad',
		'precio_unitario',
		'descripcion'
	];

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}

	public function id_servicio()
	{
		return $this->belongsTo(Servicio::class, 'id_servicio');
	}
}
