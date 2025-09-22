<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\catalago_pago;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\reserva\CargoReserva;
use App\Models\reserva\ReservaPago;
use App\Models\CreditoRespaldo;
use App\Models\check_out\Folio;	

/**
 * Class TransaccionPago
 * 
 * @property int $id_transaccion_pago
 * @property int|null $id_reserva_pago
 * @property int|null $id_metodo_pago
 * @property int|null $id_folio
 * @property int|null $id_credito
 * @property int|null $id_tipo_transaccion
 * @property string|null $resultado
 * @property int|null $id_cargo_reserva
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class TransaccionPago extends Model
{
	protected $table = 'transaccion_pago';
	protected $primaryKey = 'id_transaccion_pago';

	protected $casts = [
		'id_reserva_pago' => 'int',
		'id_metodo_pago' => 'int',
		'id_folio' => 'int',
		'id_credito' => 'int',
		'id_tipo_transaccion' => 'int',
		'id_cargo_reserva' => 'int'
	];

	protected $fillable = [
		'id_reserva_pago',
		'id_metodo_pago',
		'id_folio',
		'id_credito',
		'id_tipo_transaccion',
		'resultado',
		'id_cargo_reserva'
	];

	public function id_cargo_reserva()
	{
		return $this->belongsTo(CargoReserva::class, 'id_cargo_reserva');
	}

	public function id_credito()
	{
		return $this->belongsTo(CreditoRespaldo::class, 'id_credito');
	}

	public function id_folio()
	{
		return $this->belongsTo(Folio::class, 'id_folio');
	}

	public function id_metodo_pago()
	{
		return $this->belongsTo(MetodoPago::class, 'id_metodo_pago');
	}

	public function id_reserva_pago()
	{
		return $this->belongsTo(ReservaPago::class, 'id_reserva_pago');
	}

	public function id_tipo_transaccion()
	{
		return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
	}
}
