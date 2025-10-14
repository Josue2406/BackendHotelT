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
		'monto_pagado' => 'float',
		'monto_pendiente' => 'float',
		'porcentaje_minimo_pago' => 'float',
		'pago_completo' => 'boolean',
		'id_fuente' => 'int'
	];

	protected $fillable = [
		'id_cliente',
		'id_estado_res',
		'fecha_creacion',
		'total_monto_reserva',
		'monto_pagado',
		'monto_pendiente',
		'porcentaje_minimo_pago',
		'pago_completo',
		'notas',
		'id_fuente'
	];

	protected $appends = ['porcentaje_pagado', 'resumen_pagos'];

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
					->withPivot('id_reserva_serv', 'cantidad', 'precio_unitario', 'descripcion', 'fecha_servicio', 'subtotal')
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

/**
 * Calcular el total pagado sumando todos los pagos completados
 */
public function calcularMontoPagado(): float
{
    return $this->pagos()
        ->whereIn('id_estado_pago', [
            \App\Models\catalago_pago\EstadoPago::ESTADO_COMPLETADO,
            \App\Models\catalago_pago\EstadoPago::ESTADO_PARCIAL
        ])
        ->sum('monto');
}

/**
 * Calcular el monto pendiente de pago
 */
public function calcularMontoPendiente(): float
{
    $pagado = $this->calcularMontoPagado();
    $pendiente = $this->total_monto_reserva - $pagado;
    return max(0, $pendiente); // No puede ser negativo
}

/**
 * Verificar si se alcanzó el pago mínimo requerido
 */
public function alcanzoPagoMinimo(): bool
{
    if ($this->total_monto_reserva == 0) {
        return true;
    }

    $porcentajePagado = ($this->monto_pagado / $this->total_monto_reserva) * 100;
    return $porcentajePagado >= $this->porcentaje_minimo_pago;
}

/**
 * Verificar si el pago está completo
 */
public function estaPagadoCompleto(): bool
{
    return $this->monto_pendiente <= 0.01; // Tolerancia de 1 centavo
}

/**
 * Obtener el porcentaje pagado
 */
public function getPorcentajePagadoAttribute(): float
{
    if ($this->total_monto_reserva == 0) {
        return 100.0;
    }

    return round(($this->monto_pagado / $this->total_monto_reserva) * 100, 2);
}

/**
 * Actualizar montos de pago
 */
public function actualizarMontosPago(): void
{
    $montoPagado = $this->calcularMontoPagado();
    $montoPendiente = $this->total_monto_reserva - $montoPagado;
    $montoPendiente = max(0, $montoPendiente);
    $pagoCompleto = $montoPendiente <= 0.01;

    $this->updateQuietly([
        'monto_pagado' => $montoPagado,
        'monto_pendiente' => $montoPendiente,
        'pago_completo' => $pagoCompleto,
    ]);
}

/**
 * Obtener información resumida de pagos
 */
public function getResumenPagosAttribute(): array
{
    return [
        'total_reserva' => $this->total_monto_reserva,
        'monto_pagado' => $this->monto_pagado,
        'monto_pendiente' => $this->monto_pendiente,
        'porcentaje_pagado' => $this->porcentaje_pagado,
        'porcentaje_minimo_requerido' => $this->porcentaje_minimo_pago,
        'alcanzo_minimo' => $this->alcanzoPagoMinimo(),
        'pago_completo' => $this->pago_completo,
        'puede_confirmar' => $this->alcanzoPagoMinimo(),
    ];
}

}
