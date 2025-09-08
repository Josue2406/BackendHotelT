<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Pai
 * 
 * @property int $id_pais
 * @property string $codigo_iso2
 * @property string $codigo_iso3
 * @property string $nombre
 * 
 * @property Collection|Cliente[] $clientes_where_id_pai
 *
 * @package App\Models
 */
class Pai extends Model
{
	protected $table = 'pais';
	protected $primaryKey = 'id_pais';
	public $timestamps = false;

	protected $fillable = [
		'codigo_iso2',
		'codigo_iso3',
		'nombre'
	];

	public function clientes_where_id_pai()
	{
		return $this->hasMany(Cliente::class, 'id_pais');
	}
}
