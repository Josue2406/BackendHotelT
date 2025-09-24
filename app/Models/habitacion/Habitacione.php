<?php

namespace App\Models\habitacion;

use App\Models\check_in\AsignacionHabitacion;
use App\Models\habitacion\EstadoHabitacion;
use App\Models\habitacion\TiposHabitacion;
use App\Models\house_keeping\HabBloqueoOperativo;
use App\Models\house_keeping\Limpieza;
use App\Models\house_keeping\Mantenimiento;
use App\Models\reserva\ReservaHabitacion;

use App\Services\PricingService;          // <-- para calcular precios
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Habitacione extends Model
{
    use SoftDeletes;

    protected $table = 'habitaciones';
    protected $primaryKey = 'id_habitacion';

    protected $casts = [
        'id_estado_hab'      => 'int',
        'tipo_habitacion_id' => 'int',
        'piso'               => 'int',
        'capacidad'          => 'int',
        'precio_base'        => 'decimal:2',   // <-- nuevo
    ];

    protected $fillable = [
        'id_estado_hab',
        'tipo_habitacion_id',
        'nombre',
        'numero',
        'piso',
        'capacidad',
        'medida',
        'descripcion',
        'precio_base',   // <-- nuevo
        'moneda',        // <-- nuevo (ej. 'CRC', 'USD')
    ];

    /* =========================
     |   Relaciones principales
     * ========================= */

    // SUGERENCIA: usa nombres semánticos; dejas alias al final por retro-compatibilidad
    public function estado()
    {
        return $this->belongsTo(EstadoHabitacion::class, 'id_estado_hab', 'id_estado_hab');
    }

    public function tipo()
    {
        return $this->belongsTo(TiposHabitacion::class, 'tipo_habitacion_id', 'id_tipo_hab');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionHabitacion::class, 'id_hab', 'id_habitacion');
    }

    public function bloqueosOperativos()
    {
        return $this->hasMany(HabBloqueoOperativo::class, 'id_habitacion', 'id_habitacion');
    }

    public function amenidades()
    {
        // Ajusta el modelo/pivot si tu naming difiere
        return $this->hasMany(HabitacionAmenidad::class, 'id_habitacion', 'id_habitacion');
    }

    public function limpiezas()
    {
        return $this->hasMany(Limpieza::class, 'id_habitacion', 'id_habitacion');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_habitacion', 'id_habitacion');
    }

    public function reservasHabitacion()
    {
        return $this->hasMany(ReservaHabitacion::class, 'id_habitacion', 'id_habitacion');
    }

    /* =====================================
     |   Scopes útiles (disponibilidad/fitros)
     * ===================================== */

    /**
     * Filtra habitaciones sin asignación confirmada/ocupada en el rango [inicio, fin)
     * Nota: esto revisa asignaciones concretas; si manejas reservas por pool,
     *       complementa con tu lógica de conteo por tipo.
     */
    public function scopeDisponiblesEntre($query, Carbon $inicio, Carbon $fin)
    {
        return $query->whereDoesntHave('asignaciones.reserva', function ($q) use ($inicio, $fin) {
            $q->whereIn('estado', ['confirmada', 'check-in'])
              ->where('fecha_inicio', '<', $fin->toDateString())
              ->where('fecha_fin', '>', $inicio->toDateString());
        })->whereDoesntHave('bloqueosOperativos', function ($q) use ($inicio, $fin) {
            $q->where('fecha_inicio', '<', $fin->toDateTimeString())
              ->where('fecha_fin', '>', $inicio->toDateTimeString());
        });
    }

    /**
     * Filtra por un conjunto de features/amenidades garantizadas.
     * Si tu relación amenidades es many-to-many, ajusta a whereHas con count exacto.
     * Aquí, como es hasMany a un detalle, exigimos que existan todos los feature_id requeridos.
     */
    public function scopeWithFeatures($query, array $featureIds)
    {
        if (empty($featureIds)) return $query;

        foreach ($featureIds as $fid) {
            $query->whereHas('amenidades', fn($q) => $q->where('id_amenidad', $fid));
        }
        return $query;
    }

    /* =========================
     |   Cálculo de precios
     * ========================= */

    /**
     * Precio para UNA noche en una fecha dada.
     * Retorna array: ['base'=>float,'final'=>float,'regla'=>..., 'temporada'=>...]
     */
    public function precioNoche(Carbon $fecha): array
    {
        /** @var PricingService $svc */
        $svc = app(PricingService::class);
        return $svc->precioNoche($this, $fecha);
    }

    /**
     * Precio para un rango [checkin, checkout) sumando noche a noche.
     * Retorna array: ['noches','base_total','final_total','detalle'=>[...]]
     */
    public function precioRango(Carbon $checkin, Carbon $checkout): array
    {
        /** @var PricingService $svc */
        $svc = app(PricingService::class);
        return $svc->precioRango($this, $checkin, $checkout);
    }

    /* ======================================
     |   Aliases legacy (mantener compat)
     * ====================================== */

    // Mantengo tus aliases por si ya los usas con ->with('estado') ó ->with('tipo')
    public function id_estado_hab()
    {
        return $this->estado();
    }

    public function tipo_habitacion_id()
    {
        return $this->tipo();
    }

    // Mantengo los nombres originales de colecciones por compatibilidad
    public function asignacion_habitacions_where_id_hab()
    {
        return $this->asignaciones();
    }

    public function bloqueo_operativos()
    {
        return $this->bloqueosOperativos();
    }

    public function habitacion_amenidads_where_id_habitacion()
    {
        return $this->amenidades();
    }

    public function limpiezas_where_id_habitacion()
    {
        return $this->limpiezas();
    }

    public function mantenimientos_where_id_habitacion()
    {
        return $this->mantenimientos();
    }

    public function reserva_habitacions_where_id_habitacion()
    {
        return $this->reservasHabitacion();
    }
}
