<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\habitacion\Habitacione;

/**
 * Class ReservaHabitacion
 * 
 * @property int $id_reserva_hab
 * @property int|null $id_reserva
 * @property int|null $id_habitacion
 * @property Carbon $fecha_llegada
 * @property Carbon $fecha_salida
 * @property int $adultos
 * @property int $ninos
 * @property int $bebes
 * @property float $subtotal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|CreditoRespaldo[] $credito_respaldos_where_id_reserva_hab
 * @property Collection|Folio[] $folios_where_id_reserva_hab
 *
 * @package App\Models
 */
class ReservaHabitacion extends Model
{
	protected $table = 'reserva_habitacions';
	protected $primaryKey = 'id_reserva_hab';

	protected $casts = [
		'id_reserva' => 'int',
		'id_habitacion' => 'int',
		'fecha_llegada' => 'datetime',
		'fecha_salida' => 'datetime',
		'adultos' => 'int',
		'ninos' => 'int',
		'bebes' => 'int',
		'subtotal' => 'float'
	];

	protected $fillable = [
		'id_reserva',
		'id_habitacion',
		'fecha_llegada',
		'fecha_salida',
		'adultos',
		'ninos',
		'bebes',
		'subtotal'
	];

	/*
	public function hayChoqueHab(int $idHab, string $desde, string $hasta): bool
{
    // fin exclusivo: [llegada, salida)
    return ReservaHabitacion::query()
        ->where('id_habitacion', $idHab)
        ->where('fecha_llegada', '<', $hasta)
        ->where('fecha_salida',  '>', $desde)
        ->exists();
} */
	public function id_habitacion()
	{
		return $this->belongsTo(Habitacione::class, 'id_habitacion');
	}

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}

	// Alias legibles
	public function reserva()
	{
		return $this->id_reserva();
	}

	public function habitacion()
	{
		return $this->id_habitacion();
	}

	public function credito_respaldos_where_id_reserva_hab()
	{
		return $this->hasMany(CreditoRespaldo::class, 'id_reserva_hab');
	}

	public function folios_where_id_reserva_hab()
	{
		return $this->hasMany(Folio::class, 'id_reserva_hab');
	}

	/**
	 * Calcular el subtotal de esta habitación
	 * (noches × precio_base de la habitación)
	 * TODO: Integrar con PricingService para aplicar temporadas
	 */
	public function calcularSubtotal(): float
	{
		if (!$this->habitacion || !$this->fecha_llegada || !$this->fecha_salida) {
			return 0.0;
		}

		// Calcular noches
		$noches = $this->fecha_llegada->diffInDays($this->fecha_salida);

		// Precio base de la habitación (desde la tabla habitaciones, no tipo_habitacion)
		$precioBase = $this->habitacion->precio_base ?? 0;

		return round($noches * $precioBase, 2);
	}
}
