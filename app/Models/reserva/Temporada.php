<?php

namespace App\Models\reserva;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Temporada extends Model
{
    protected $table = 'temporadas';
    protected $primaryKey = 'id_temporada';

    protected $fillable = [
        'nombre',       // antes era "campo"
        'fecha_ini',
        'fecha_fin',
        'prioridad',    // nuevo
        'activo',       // nuevo
    ];

    protected $casts = [
        // Si tus columnas son DATE en BD, es mejor 'date' para evitar horas
        'fecha_ini' => 'date',
        'fecha_fin' => 'date',
        'activo'    => 'boolean',
        'prioridad' => 'integer',
    ];

    /* ==========
     | Relaciones
     * ========== */

    // Si ya tienes tarifas asociadas a temporada, la mantengo
    public function tarifas_where_id_temporada()
    {
        return $this->hasMany(Tarifa::class, 'id_temporada', 'id_temporada');
    }

    // Relación con reglas de temporada (para ajustes de precio)
    public function reglas()
    {
        // Ajusta el namespace del modelo si lo colocaste en otro módulo
        return $this->hasMany(\App\Models\TemporadaRegla::class, 'id_temporada', 'id_temporada');
    }

    /* =======
     | Scopes
     * ======= */

    // Temporadas activas
    public function scopeActivas($query)
    {
        return $query->where('activo', 1);
    }

    // Temporadas que cubren una fecha específica
    public function scopeCubreFecha($query, $fecha)
    {
        $f = $fecha instanceof Carbon ? $fecha->toDateString() : (string)$fecha;
        return $query->whereDate('fecha_ini', '<=', $f)
                     ->whereDate('fecha_fin', '>=', $f);
    }

    // Temporadas que se solapan con un rango [ini, fin)
    public function scopeSolapaRango($query, $ini, $fin)
    {
        $i = $ini instanceof Carbon ? $ini->toDateString() : (string)$ini;
        $f = $fin instanceof Carbon ? $fin->toDateString() : (string)$fin;

        // solape típico: ini < fecha_fin && fin > fecha_ini
        return $query->whereDate('fecha_ini', '<',  $f)
                     ->whereDate('fecha_fin',  '>', $i);
    }

    /* ============================
     | Helpers (por conveniencia)
     * ============================ */

    public function cubre(Carbon $fecha): bool
    {
        return $fecha->toDateString() >= $this->fecha_ini->toDateString()
            && $fecha->toDateString() <= $this->fecha_fin->toDateString()
            && $this->activo === true;
    }

    /* ======================================
     | Compatibilidad hacia atrás (opcional)
     * ====================================== */

    // Si en algún punto tu app aún lee "campo", puedes exponer un accessor simple:
    public function getCampoAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setCampoAttribute($value): void
    {
        $this->attributes['nombre'] = $value;
    }
}
