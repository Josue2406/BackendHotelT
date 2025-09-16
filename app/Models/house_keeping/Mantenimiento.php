<?php

namespace App\Models\house_keeping;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\habitacion\Habitacione;
use App\Models\usuario\User;
use App\Models\habitacion\EstadoHabitacion; // <-- nuevo import

/**
 * Class Mantenimiento
 *
 * @property int $id_mantenimiento
 * @property string $nombre
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_final
 * @property string|null $descripcion
 * @property Carbon $fecha_reporte
 * @property string|null $notas
 * @property string|null $prioridad
 * @property int|null $id_usuario_asigna
 * @property int|null $id_usuario_reporta
 * @property int|null $id_habitacion
 * @property int|null $id_estado_hab           // <-- nuevo campo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';
    protected $primaryKey = 'id_mantenimiento';

    protected $casts = [
        'fecha_inicio'       => 'datetime',
        'fecha_final'        => 'datetime',
        'fecha_reporte'      => 'datetime',
        'id_usuario_asigna'  => 'int',
        'id_usuario_reporta' => 'int',
        'id_habitacion'      => 'int',
        'id_estado_hab'      => 'int',   // <-- cast del nuevo campo
    ];

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_final',
        'descripcion',
        'fecha_reporte',
        'notas',
        'prioridad',
        'id_usuario_asigna',
        'id_usuario_reporta',
        'id_habitacion',
        'id_estado_hab',     // <-- fillable del nuevo campo
    ];

    /** Relaciones */

    // Habitacion a la que pertenece el mantenimiento
    public function habitacion()
    {
        return $this->belongsTo(Habitacione::class, 'id_habitacion');
    }

    // Usuario que asigna el mantenimiento
    public function asignador()
    {
        return $this->belongsTo(User::class, 'id_usuario_asigna');
    }

    // Usuario que reporta el mantenimiento
    public function reportante()
    {
        return $this->belongsTo(User::class, 'id_usuario_reporta');
    }

    // Estado de habitación asociado (nuevo)
    public function estadoHabitacion()
    {
        return $this->belongsTo(EstadoHabitacion::class, 'id_estado_hab');
    }

    // Historial de mantenimientos
    public function historialMantenimientos()
    {
        return $this->hasMany(HistorialMantenimiento::class, 'id_mantenimiento');
    }
}
