<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TipoDoc
 * 
 * @property int $id_tipo_doc
 * @property string $nombre
 * 
 * @property Collection|Cliente[] $clientes_where_id_tipo_doc
 *
 * @package App\Models
 */
class TipoDoc extends Model
{
	protected $table = 'tipo_doc';
	protected $primaryKey = 'id_tipo_doc';
	public $timestamps = false;

	protected $fillable = [
		'nombre'
	];

	public function clientes_where_id_tipo_doc()
	{
		return $this->hasMany(Cliente::class, 'id_tipo_doc');
	}
}
