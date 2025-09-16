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
