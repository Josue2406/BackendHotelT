<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\cliente;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cliente
 * 
 * @property int $id_cliente
 * @property string $nombre
 * @property string $apellido1
 * @property string|null $apellido2
 * @property string $email
 * @property string $telefono
 * @property int|null $id_tipo_doc
 * @property string|null $numero_doc
 * @property int|null $id_pais
 * @property string|null $direccion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Estadium[] $estadia_where_id_cliente_titular
 * @property Collection|Reserva[] $reservas_where_id_cliente
 *
 * @package App\Models
 */
class Cliente extends Model
{
	protected $table = 'clientes';
	protected $primaryKey = 'id_cliente';

	protected $casts = [
		'id_tipo_doc' => 'int',
		'id_pais' => 'int'
	];

	protected $fillable = [
		'nombre',
		'apellido1',
		'apellido2',
		'email',
		'telefono',
		'id_tipo_doc',
		'numero_doc',
		'id_pais',
		'direccion'
	];

	public function id_pais()
	{
		return $this->belongsTo(Pai::class, 'id_pais');
	}

	public function id_tipo_doc()
	{
		return $this->belongsTo(TipoDoc::class, 'id_tipo_doc');
	}

	public function estadia_where_id_cliente_titular()
	{
		return $this->hasMany(Estadium::class, 'id_cliente_titular');
	}

	public function reservas_where_id_cliente()
	{
		return $this->hasMany(Reserva::class, 'id_cliente');
	}
}
