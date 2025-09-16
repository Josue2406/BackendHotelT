<?php

namespace App\Models\house_keeping;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\habitacion\Habitacione;
use App\Models\usuario\User;
use App\Models\habitacion\EstadoHabitacion; // <-- nuevo import

/**
 * Class Limpieza
 *
 * @property int $id_limpieza
 * @property string $nombre
 * @property Carbon $fecha_inicio
 * @property Carbon|null $fecha_final
 * @property string|null $descripcion
 * @property Carbon $fecha_reporte
 * @property string|null $notas
 * @property string|null $prioridad
 * @property int|null $id_usuario_asigna
 * @property int|null $id_usuario_reporta
 * @property int|null $id_habitacion
 * @property int|null $id_estado_hab            // <-- nuevo campo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Limpieza extends Model
{
    protected $table = 'limpiezas';
    protected $primaryKey = 'id_limpieza';

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

    // Habitacion a la que pertenece la limpieza
    public function habitacion()
    {
        return $this->belongsTo(Habitacione::class, 'id_habitacion');
    }

    // Usuario que asigna la limpieza
    public function asignador()
    {
        return $this->belongsTo(User::class, 'id_usuario_asigna');
    }

    // Usuario que reporta la limpieza
    public function reportante()
    {
        return $this->belongsTo(User::class, 'id_usuario_reporta');
    }

    // Estado de habitación asociado (nuevo)
    public function estadoHabitacion()
    {
        return $this->belongsTo(EstadoHabitacion::class, 'id_estado_hab');
    }

    // Historial
    public function historialLimpiezas()
    {
        return $this->hasMany(HistorialLimpieza::class, 'id_limpieza');
    }
}
