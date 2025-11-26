<?php

// app/Models/TemporadaRegla.php
namespace App\Models\reserva;

use Illuminate\Database\Eloquent\Model;

class TemporadaRegla extends Model
{
    protected $table = 'temporada_reglas';
    protected $primaryKey = 'id_regla';

    protected $fillable = [
      'id_temporada','scope','tipo_habitacion_id','habitacion_id',
      'tipo_ajuste','valor','prioridad','aplica_dow','min_noches'
    ];

    protected $casts = [
      'valor' => 'decimal:2',
    ];

    public function temporada()
    {
        return $this->belongsTo(Temporada::class, 'id_temporada', 'id_temporada');
    }
}
