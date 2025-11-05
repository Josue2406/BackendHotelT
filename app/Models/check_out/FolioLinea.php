<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Model;

class FolioLinea extends Model
{
    //
    protected $table = 'folio_linea';
    protected $primaryKey = 'id_folio_linea';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_folio',
        'id_cliente',
        'descripcion',
        'monto',
    ];

    // Relaciones (ajusta los namespaces de Folio y Cliente si difieren)
    public function folio()
    {
        return $this->belongsTo(Folio::class, 'id_folio', 'id_folio');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
