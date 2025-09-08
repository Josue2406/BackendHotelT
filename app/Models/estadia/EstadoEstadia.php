<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EstadoEstadium
 * 
 * @property int $id_estado_estadia
 * @property string $codigo
 * @property string $nombre
 * 
 * @property Collection|Estadium[] $estadia_where_id_estado_estadium
 *
 * @package App\Models
 */
class EstadoEstadium extends Model
{
	protected $table = 'estado_estadia';
	protected $primaryKey = 'id_estado_estadia';
	public $timestamps = false;

	protected $fillable = [
		'codigo',
		'nombre'
	];

	public function estadia_where_id_estado_estadium()
	{
		return $this->hasMany(Estadium::class, 'id_estado_estadia');
	}
}
