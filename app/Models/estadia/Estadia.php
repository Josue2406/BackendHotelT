<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\estadia;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;


// ==== IMPORTS: AJUSTA ESTOS NAMESPACES A TU ESTRUCTURA REAL ====
use App\Models\cliente\Cliente;                 // <-- AJUSTAR
use App\Models\estadia\EstadoEstadia;           // <-- AJUSTAR
use App\Models\estadia\Fuente;                 // <-- AJUSTAR
use App\Models\reserva\Reserva;                 // <-- AJUSTAR
use App\Models\check_in\AsignacionHabitacion;   // <-- AJUSTAR
use App\Models\check_out\Folio;                     // <-- AJUSTAR



/** 
 * Class Estadia
 * 
 * @property int $id_estadia
 * @property int|null $id_reserva
 * @property int $id_cliente_titular
 * @property int|null $id_fuente
 * @property Carbon $fecha_llegada
 * @property Carbon $fecha_salida
 * @property int $adultos
 * @property int $ninos
 * @property int $bebes
 * @property int|null $id_estado_estadia
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|AsignacionHabitacion[] $asignacion_habitacions_where_id_estadium
 * @property Collection|Folio[] $folios_where_id_estadium
 *
 * @package App\Models
 */
class Estadia extends Model
{
	protected $table = 'estadia';
	protected $primaryKey = 'id_estadia';

	protected $casts = [
		'id_reserva' => 'int',
		'id_cliente_titular' => 'int',
		'id_fuente' => 'int',
		'fecha_llegada' => 'datetime',
		'fecha_salida' => 'datetime',
		'adultos' => 'int',
		'ninos' => 'int',
		'bebes' => 'int',
		'id_estado_estadia' => 'int'
	];

	protected $fillable = [
		'id_reserva',
		'id_cliente_titular',
		'id_fuente',
		'fecha_llegada',
		'fecha_salida',
		'adultos',
		'ninos',
		'bebes',
		'id_estado_estadia'
	];
/*
	public function id_cliente_titular()
	{
		return $this->belongsTo(Cliente::class, 'id_cliente_titular');
	}

	public function id_estado_estadia()
	{
		return $this->belongsTo(EstadoEstadia::class, 'id_estado_estadia');
	}

	public function id_fuente()
	{
		return $this->belongsTo(Fuente::class, 'id_fuente');
	}

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}

	public function asignacion_habitacions_where_id_estadia()
	{
		return $this->hasMany(AsignacionHabitacion::class, 'id_estadia');
	}

	public function folios_where_id_estadia()
	{
		return $this->hasMany(Folio::class, 'id_estadia');
	}
}

*/
 /** ---------------- Relaciones belongsTo (nombres semánticos) ---------------- */

 public function estadoEstadia()
    {
        // Modelo de catálogo/estado de la estadía
        return $this->belongsTo(\App\Models\estadia\EstadoEstadia::class, 'id_estado_estadia', 'id_estado_estadia');
    }
	
    public function clienteTitular()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente_titular', 'id_cliente');
        // ajusta 'id_cliente' si tu PK se llama diferente
    }

    public function estado()
    {
        return $this->belongsTo(EstadoEstadia::class, 'id_estado_estadia', 'id_estado_estadia');
    }

    public function fuente()
    {
        return $this->belongsTo(Fuente::class, 'id_fuente', 'id_fuente');
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva', 'id_reserva');
    }

    /** ---------------- Relaciones hasMany/hasOne (nombres legibles) -------------- */

    public function asignaciones()
    {
        return $this->hasMany(AsignacionHabitacion::class, 'id_estadia', 'id_estadia');
    }

    public function folios()
    {
        return $this->hasMany(Folio::class, 'id_estadia', 'id_estadia');
    }

	
    /** ---------------- Helpers útiles ------------------------------------------- */

    // Última asignación por fecha_asignacion
    public function ultimaAsignacion()
    {
        return $this->hasOne(AsignacionHabitacion::class, 'id_estadia', 'id_estadia')->latestOfMany('fecha_asignacion');
    }

    // Asignación activa (sin fecha de desasignación)
    public function asignacionActiva()
    {
        return $this->hasOne(AsignacionHabitacion::class, 'id_estadia', 'id_estadia')
            ->whereNull('fecha_desasignacion');
    }
}








