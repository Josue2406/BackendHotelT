<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HistorialReserva
 * 
 * @property int $id_hist_res
 * @property int $id_reserva
 * @property int $id_usuario
 * @property string $campo
 * @property string|null $valor_anterior
 * @property string|null $valor_nuevo
 * @property string|null $motivo
 * @property Carbon $fecha
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class HistorialReserva extends Model
{
	protected $table = 'historial_reserva';
	protected $primaryKey = 'id_hist_res';

	protected $casts = [
		'id_reserva' => 'int',
		'id_usuario' => 'int',
		'fecha' => 'datetime'
	];

	protected $fillable = [
		'id_reserva',
		'id_usuario',
		'campo',
		'valor_anterior',
		'valor_nuevo',
		'motivo',
		'fecha'
	];

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}

	public function id_usuario()
	{
		return $this->belongsTo(User::class, 'id_usuario');
	}
}
