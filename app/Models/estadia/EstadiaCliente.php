<?php

/**
 * Created manually for EstadiaCliente table
 */

namespace App\Models\estadia;

use Illuminate\Database\Eloquent\Model;
use App\Models\clientes\Cliente;

/**
 * Class EstadiaCliente
 * 
 * @property int $id
 * @property int $id_estadia
 * @property int $id_cliente
 * @property string|null $rol
 * @property string|null $created_at
 * 
 * @property Estadia $estadia
 * @property Cliente $cliente
 *
 * @package App\Models
 */
class EstadiaCliente extends Model
{
    protected $table = 'estadia_cliente';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = false; // no tiene updated_at

    protected $fillable = [
        'id_estadia',
        'id_cliente',
        'rol',
        'created_at'
    ];

    /** Relaciones */
    public function estadia()
    {
        return $this->belongsTo(Estadia::class, 'id_estadia', 'id_estadia');
    }

    public function cliente()
{
    return $this->belongsTo(\App\Models\cliente\Cliente::class, 'id_cliente', 'id_cliente');
}

}
