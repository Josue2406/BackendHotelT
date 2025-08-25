<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoHabitacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_habitacion';

    protected $fillable = [
        'nombre', 'codigo', 'capacidad', 'tarifa_base', 'amenidades', 'descripcion',
    ];

    protected $casts = [
        'amenidades' => 'array',
        'tarifa_base' => 'decimal:2',
        'capacidad'   => 'integer',
    ];

    public function habitaciones()
    {
        return $this->hasMany(Habitacion::class, 'tipo_habitacion_id');
    }
}

