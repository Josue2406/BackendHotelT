<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EstadoFolio
 * 
 * @property int $id_estado_folio
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Folio[] $folios_where_id_estado_folio
 *
 * @package App\Models
 */
class EstadoFolio extends Model
{
	protected $table = 'estado_folio';
	protected $primaryKey = 'id_estado_folio';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function folios_where_id_estado_folio()
	{
		return $this->hasMany(Folio::class, 'id_estado_folio');
	}
}
