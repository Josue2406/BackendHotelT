<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Rol
 * 
 * @property int $id_rol
 * @property string $nombre
 * @property string $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|User[] $users_where_id_rol
 *
 * @package App\Models
 */
class Rol extends Model
{
	protected $table = 'rols';
	protected $primaryKey = 'id_rol';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function users_where_id_rol()
	{
		return $this->hasMany(User::class, 'id_rol');
	}
}
