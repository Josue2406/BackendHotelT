<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use App\Models\cliente\Cliente;
use App\Models\reserva\EstadoReserva;
use App\Models\estadia\Fuente;
use App\Models\reserva\ReservaHabitacion;
use App\Models\reserva\ReservaPolitica;
use App\Models\reserva\ReservaPago;
use App\Models\check_in\AsignacionHabitacion;
use App\Models\estadia\Estadia;
use App\Models\reserva\Servicio;


/**
 * Class Reserva
 *
 * @property int $id_reserva
 * @property int $id_cliente
 * @property int $id_estado_res
 * @property Carbon $fecha_creacion
 * @property float $total_monto_reserva
 * @property string|null $notas
 * @property int|null $id_fuente
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|AsignacionHabitacion[] $asignacion_habitacions_where_id_reserva
 * @property Collection|Estadia[] $estadia_where_id_reserva
 * @property Collection|ReservaHabitacion[] $reserva_habitacions_where_id_reserva
 * @property Collection|ReservaPago[] $reserva_pagos_where_id_reserva
 * @property Collection|ReservaPolitica[] $reserva_politicas_where_id_reserva
 * @property Collection|Servicio[] $servicios
 *
 * @package App\Models
 */
class Reserva extends Model
{
	protected $table = 'reserva';
	protected $primaryKey = 'id_reserva';

	protected $casts = [
		'id_cliente' => 'int',
		'id_estado_res' => 'int',
		'fecha_creacion' => 'datetime',
		'total_monto_reserva' => 'float',
		'id_fuente' => 'int'
	];

	protected $fillable = [
		'id_cliente',
		'id_estado_res',
		'fecha_creacion',
		'total_monto_reserva',
		'notas',
		'id_fuente'
	];

	public function id_cliente()
	{
		return $this->belongsTo(Cliente::class, 'id_cliente');
	}

	public function id_estado_res()
	{
		return $this->belongsTo(EstadoReserva::class, 'id_estado_res');
	}

	public function id_fuente()
	{
		return $this->belongsTo(Fuente::class, 'id_fuente');
	}

	public function asignacion_habitacions_where_id_reserva()
	{
		return $this->hasMany(AsignacionHabitacion::class, 'id_reserva');
	}

	public function estadia_where_id_reserva()
	{
		return $this->hasMany(Estadia::class, 'id_reserva');
	}

	public function reserva_habitacions_where_id_reserva()
	{
		return $this->hasMany(ReservaHabitacion::class, 'id_reserva');
	}

	public function reserva_pagos_where_id_reserva()
	{
		return $this->hasMany(ReservaPago::class, 'id_reserva');
	}

	public function reserva_politicas_where_id_reserva()
	{
		return $this->hasMany(ReservaPolitica::class, 'id_reserva');
	}

	public function servicios()
	{
		return $this->belongsToMany(Servicio::class, 'reserva_servicio', 'id_reserva', 'id_servicio')
					->withPivot('id_reserva_serv', 'cantidad', 'precio_unitario', 'descripcion')
					->withTimestamps();
	}

	// Alias legibles para usar en with(), load(), etc.
public function cliente()
{
    return $this->id_cliente();
}

public function estado()
{
    return $this->id_estado_res();
}

public function fuente()
{
    return $this->id_fuente();
}

public function habitaciones()
{
    return $this->reserva_habitacions_where_id_reserva();
}

public function politicas()
{
    return $this->reserva_politicas_where_id_reserva();
}

public function pagos()
{
    return $this->reserva_pagos_where_id_reserva();
}

public function asignaciones()
{
    return $this->asignacion_habitacions_where_id_reserva();
}

public function estadias()
{
    return $this->estadia_where_id_reserva();
}

}
