<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\chech_in;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EstadoCredito
 * 
 * @property int $id_estado_credito
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|CreditoRespaldo[] $credito_respaldos_where_id_estado_credito
 *
 * @package App\Models
 */
class EstadoCredito extends Model
{
	protected $table = 'estado_credito';
	protected $primaryKey = 'id_estado_credito';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function credito_respaldos_where_id_estado_credito()
	{
		return $this->hasMany(CreditoRespaldo::class, 'id_estado_credito');
	}
}
