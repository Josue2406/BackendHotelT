<?php

namespace App\Models\habitacion;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\house_keeping\Mantenimiento;
use App\Models\house_keeping\Limpieza;

/**
 * Class EstadoHabitacion
 *
 * @property int $id_estado_hab
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Habitacione[] $habitaciones
 * @property Collection|Mantenimiento[] $mantenimientos
 * @property Collection|Limpieza[] $limpiezas
 */
class EstadoHabitacion extends Model
{
    protected $table = 'estado_habitacions';
    protected $primaryKey = 'id_estado_hab';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
    ];

    // Constantes para estados de habitación
    public const ESTADO_DISPONIBLE = 1;
    public const ESTADO_OCUPADA = 2;
    public const ESTADO_SUCIA = 3;
    public const ESTADO_LIMPIA = 4;
    public const ESTADO_MANTENIMIENTO = 5;

    // Nombres de estados
    public const ESTADOS_VALIDOS = [
        self::ESTADO_DISPONIBLE => 'Disponible',
        self::ESTADO_OCUPADA => 'Ocupada',
        self::ESTADO_SUCIA => 'Sucia',
        self::ESTADO_LIMPIA => 'Limpia',
        self::ESTADO_MANTENIMIENTO => 'Mantenimiento',
    ];

    /** Relaciones */

    // Habitaciones que tienen este estado
    public function habitaciones()
    {
        return $this->hasMany(Habitacione::class, 'id_estado_hab');
    }

    // Mantenimientos asociados a este estado
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_estado_hab');
    }

    // Limpiezas asociadas a este estado
    public function limpiezas()
    {
        return $this->hasMany(Limpieza::class, 'id_estado_hab');
    }
}
