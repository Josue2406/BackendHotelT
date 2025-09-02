<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cliente
 * 
 * @property int $id_cliente
 * @property string $nombre
 * @property string $apellidos
 * @property string $email
 * @property string $telefono
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Reserva[] $reservas_where_id_cliente
 *
 * @package App\Models
 */
class Cliente extends Model
{
	protected $table = 'clientes';
	protected $primaryKey = 'id_cliente';

	protected $fillable = [
		'nombre',
		'apellidos',
		'email',
		'telefono'
	];

	public function reservas_where_id_cliente()
	{
		return $this->hasMany(Reserva::class, 'id_cliente');
	}
}
