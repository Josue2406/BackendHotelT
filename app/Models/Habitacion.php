<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Habitacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'habitaciones';

    protected $fillable = [
        'tipo_habitacion_id', 'numero', 'piso', 'estado', 'tarifa_noche', 'habilitada',
    ];

    protected $casts = [
        'tarifa_noche' => 'decimal:2',
        'piso' => 'integer',
        'habilitada' => 'boolean',
    ];

    public function tipo()
    {
        return $this->belongsTo(TipoHabitacion::class, 'tipo_habitacion_id');
    }

    // Helper: devuelve la tarifa efectiva (habitacion o tipo)
    public function getTarifaEfectivaAttribute(): string
    {
        $tarifa = $this->tarifa_noche ?? optional($this->tipo)->tarifa_base ?? 0;
        return number_format((float) $tarifa, 2, '.', '');
    }
}
