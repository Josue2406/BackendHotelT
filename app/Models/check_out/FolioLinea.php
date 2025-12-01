<?php

namespace App\Models\check_out;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\cliente\Cliente;

class FolioLinea extends Model
{
    protected $table = 'folio_linea';
    protected $primaryKey = 'id_folio_linea';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'id_folio',
        'id_cliente',
        'descripcion',
        'monto',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    // Relaciones
    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class, 'id_folio', 'id_folio');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
