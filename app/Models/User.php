<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class User
 * 
 * @property int $id_usuario
 * @property int $id_rol
 * @property string $nombre
 * @property string $telefono
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property bool $activo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|HistorialLimpieza[] $historial_limpiezas_where_actor_id
 * @property Collection|HistorialMantenimiento[] $historial_mantenimientos_where_actor_id
 * @property Collection|HistorialReserva[] $historial_reservas_where_id_usuario
 * @property Collection|Limpieza[] $limpiezas_where_id_usuario_asigna
 * @property Collection|Limpieza[] $limpiezas_where_id_usuario_reportum
 * @property Collection|Mantenimiento[] $mantenimientos_where_id_usuario_asigna
 * @property Collection|Mantenimiento[] $mantenimientos_where_id_usuario_reportum
 * @property Collection|NuevaEntradaFolio[] $nueva_entrada_folios_where_id_usuario
 *
 * @package App\Models
 */
class User extends Model
{
	protected $table = 'users';
	protected $primaryKey = 'id_usuario';

	protected $casts = [
		'id_rol' => 'int',
		'email_verified_at' => 'datetime',
		'activo' => 'bool'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'id_rol',
		'nombre',
		'telefono',
		'email',
		'email_verified_at',
		'password',
		'remember_token',
		'activo'
	];

	public function id_rol()
	{
		return $this->belongsTo(Rol::class, 'id_rol');
	}

	public function historial_limpiezas_where_actor_id()
	{
		return $this->hasMany(HistorialLimpieza::class, 'actor_id');
	}

	public function historial_mantenimientos_where_actor_id()
	{
		return $this->hasMany(HistorialMantenimiento::class, 'actor_id');
	}

	public function historial_reservas_where_id_usuario()
	{
		return $this->hasMany(HistorialReserva::class, 'id_usuario');
	}

	public function limpiezas_where_id_usuario_asigna()
	{
		return $this->hasMany(Limpieza::class, 'id_usuario_asigna');
	}

	public function limpiezas_where_id_usuario_reportum()
	{
		return $this->hasMany(Limpieza::class, 'id_usuario_reporta');
	}

	public function mantenimientos_where_id_usuario_asigna()
	{
		return $this->hasMany(Mantenimiento::class, 'id_usuario_asigna');
	}

	public function mantenimientos_where_id_usuario_reportum()
	{
		return $this->hasMany(Mantenimiento::class, 'id_usuario_reporta');
	}

	public function nueva_entrada_folios_where_id_usuario()
	{
		return $this->hasMany(NuevaEntradaFolio::class, 'id_usuario');
	}
}
