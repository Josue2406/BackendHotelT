<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PoliticaCancelacion
 * 
 * @property int $id_politica
 * @property string $nombre
 * @property string $regla_ventana
 * @property string $penalidad_tipo
 * @property float $penalidad_valor
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|ReservaPolitica[] $reserva_politicas_where_id_politica
 *
 * @package App\Models
 */
class PoliticaCancelacion extends Model
{
	protected $table = 'politica_cancelacion';
	protected $primaryKey = 'id_politica';

	protected $casts = [
		'penalidad_valor' => 'float'
	];

	protected $fillable = [
		'nombre',
		'regla_ventana',
		'penalidad_tipo',
		'penalidad_valor',
		'descripcion'
	];

	public function reserva_politicas_where_id_politica()
	{
		return $this->hasMany(ReservaPolitica::class, 'id_politica');
	}
}
