<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\check_in;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\estadia\Estadia;
use App\Models\habitacion\Habitacione;
use App\Models\reserva\Reserva;
use App\Models\check_in\CheckIn;
use App\Models\check_out\CheckOut;




/**
 * Class AsignacionHabitacion
 * 
 * @property int $id_asignacion
 * @property int|null $id_hab
 * @property int|null $id_reserva
 * @property int|null $id_estadia
 * @property string $origen
 * @property string $nombre
 * @property Carbon $fecha_asignacion
 * @property int $adultos
 * @property int $ninos
 * @property int $bebes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|CheckIn[] $check_ins_where_id_asignacion
 * @property Collection|CheckOut[] $check_outs_where_id_asignacion
 *
 * @package App\Models
 */
/*
class AsignacionHabitacion extends Model
{
	protected $table = 'asignacion_habitacions';
	protected $primaryKey = 'id_asignacion';

	protected $casts = [
		'id_hab' => 'int',
		'id_reserva' => 'int',
		'id_estadia' => 'int',
		'fecha_asignacion' => 'datetime',
		'adultos' => 'int',
		'ninos' => 'int',
		'bebes' => 'int'
	];

	protected $fillable = [
		'id_hab',
		'id_reserva',
		'id_estadia',
		'origen',
		'nombre',
		'fecha_asignacion',
		'adultos',
		'ninos',
		'bebes'
	];

	public function estadia()
{
    // ajusta clase/tabla si tu modelo es Estadia/Estadium
    return $this->belongsTo(\App\Models\estadia\Estadia::class, 'id_estadia', 'id_estadia');
}

	public function id_estadia()
	{
		return $this->belongsTo(Estadia::class, 'id_estadia');
	}

	public function id_hab()
	{
		return $this->belongsTo(Habitacione::class, 'id_hab');
	}

	public function id_reserva()
	{
		return $this->belongsTo(Reserva::class, 'id_reserva');
	}

	public function check_ins_where_id_asignacion()
	{
		return $this->hasMany(CheckIn::class, 'id_asignacion');
	}

	public function check_outs_where_id_asignacion()
	{
		return $this->hasMany(CheckOut::class, 'id_asignacion');
	}


	
}
*/



class AsignacionHabitacion extends Model
{
    protected $table = 'asignacion_habitacions';
    protected $primaryKey = 'id_asignacion';

    protected $casts = [
        'id_hab' => 'int',
        'id_reserva' => 'int',
        'id_estadia' => 'int',
        'fecha_asignacion' => 'datetime',
        'adultos' => 'int',
        'ninos' => 'int',
        'bebes' => 'int'
    ];

    protected $fillable = [
        'id_hab',
        'id_reserva',
        'id_estadia',
        'origen',
        'nombre',
        'fecha_asignacion',
        'adultos',
        'ninos',
        'bebes'
    ];


	/*
    public function checkOut()
{
    return $this->hasOne(CheckOut::class, 'id_asignacion', 'id_asignacion');
} */


    public function estadia(): BelongsTo
    {
        return $this->belongsTo(Estadia::class, 'id_estadia', 'id_estadia');
    }

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacione::class, 'id_habitacion', 'id_habitacion');
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'id_reserva', 'id_reserva');
    }

	/*
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class, 'id_asignacion');
    } */

	public function checkIns()
{
    return $this->hasMany(CheckIn::class, 'id_asignacion', 'id_asignacion');
}

public function checkOuts()
{
    return $this->hasMany(CheckOut::class, 'id_asignacion', 'id_asignacion');
}

}
