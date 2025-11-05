<?php

namespace App\Models\check_out;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

use App\Models\estadia\Estadia;
use App\Models\reserva\ReservaHabitacion;
use App\Models\factura\Factura;
use App\Models\check_out\NuevaEntradaFolio;
use App\Models\catalogo_pago\TransaccionPago;

class Folio extends Model
{
    protected $table = 'folio';
    protected $primaryKey = 'id_folio';
    public $timestamps = true;

    protected $casts = [
        'id_reserva_hab' => 'int',
        'id_estadia' => 'int',
        'id_estado_folio' => 'int',
        'total' => 'float',
    ];

    protected $fillable = [
        'id_reserva_hab',
        'id_estadia',
        'id_estado_folio',
        'total',
    ];

    // ============================
    // 🔹 Relaciones
    // ============================

    public function estadia(): BelongsTo
    {
        return $this->belongsTo(Estadia::class, 'id_estadia');
    }

    public function estadoFolio(): BelongsTo
    {
        return $this->belongsTo(EstadoFolio::class, 'id_estado_folio');
    }

    public function reservaHabitacion(): BelongsTo
    {
        return $this->belongsTo(ReservaHabitacion::class, 'id_reserva_hab');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_folio');
    }

    public function nuevasEntradas(): HasMany
    {
        return $this->hasMany(NuevaEntradaFolio::class, 'id_folio');
    }

    public function transacciones(): HasMany
    {
        return $this->hasMany(TransaccionPago::class, 'id_folio');
    }

    // ============================
    // 🔹 Accesor para mostrar nombre del estado directamente
    // ============================
    protected $appends = ['estado_nombre'];

    public function getEstadoNombreAttribute(): ?string
    {
        return $this->estadoFolio ? $this->estadoFolio->nombre : null;
    }
    public function id_estado_folio()
{
    return $this->belongsTo(\App\Models\check_out\EstadoFolio::class, 'id_estado_folio', 'id_estado_folio');
}



public function lineas()
{
    return $this->hasMany(\App\Models\check_out\FolioLinea::class, 'id_folio', 'id_folio');
}

public function pagos()
{
    return $this->hasMany(\App\Models\catalogo_pago\TransaccionPago::class, 'id_folio', 'id_folio');
}

public function estado()
{
    return $this->belongsTo(\App\Models\check_out\EstadoFolio::class, 'id_estado_folio', 'id_estado_folio');
}

}
