<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ReservaPolitica
 * 
 * @property int $id_reserva_politica
 * @property int $id_politica
 * @property int $id_reserva
 * @property string|null $motivo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class ReservaPolitica extends Model
{
	protected $table = 'reserva_politica';
	protected $primaryKey = 'id_reserva_politica';

	protected $casts = [
		'id_politica' => 'int',
		'id_reserva' => 'int'
	];

	protected $fillable = [
		'id_politica',
		'id_reserva',
		'motivo'
	];

	public function id_politica()
	{
		return $this->belongsTo(PoliticaCancelacion::class, 'id_politica');
	}

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}
}
