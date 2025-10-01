<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\usuario;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
////////
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\house_keeping\HistorialLimpieza;
use App\Models\house_keeping\HistorialMantenimiento;
use App\Models\house_keeping\Limpieza;
use App\Models\house_keeping\Mantenimiento;
/**
 * Class User
 * 
 * @property int $id_usuario
 * @property int $id_rol
 * @property string $nombre
 * @property string $apellido1
 * @property string|null $apellido2
 * @property string $email
 * @property string $password
 * @property string|null $telefono
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|HistorialLimpieza[] $historial_limpiezas_where_actor_id
 * @property Collection|HistorialMantenimiento[] $historial_mantenimientos_where_actor_id
 * @property Collection|Limpieza[] $limpiezas_where_id_usuario_asigna
 * @property Collection|Limpieza[] $limpiezas_where_id_usuario_reportum
 * @property Collection|Mantenimiento[] $mantenimientos_where_id_usuario_asigna
 * @property Collection|Mantenimiento[] $mantenimientos_where_id_usuario_reportum
 * @property Collection|NuevaEntradaFolio[] $nueva_entrada_folios_where_id_usuario
 * @property Collection|ReservaPago[] $reserva_pagos_where_creado_por
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id_usuario';
    public $incrementing = true;

    protected $casts = [
        'id_rol' => 'int',
    ];

    protected $hidden = ['password'];

    protected $fillable = [
        'id_rol','nombre','apellido1','apellido2','email','password','telefono'
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

	public function historialLimpiezas()
	{
		return $this->hasMany(HistorialLimpieza::class, 'actor_id', 'id_usuario' );
	}

	public function historialMantenimientos()
	{
		return $this->hasMany(HistorialMantenimiento::class, 'actor_id', 'id_usuario' );
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

	public function reserva_pagos_where_creado_por()
	{
		return $this->hasMany(ReservaPago::class, 'creado_por');
	}
}
