<?php
namespace App\Models\cliente;

use Illuminate\Database\Eloquent\Model;

class ClientePerfilViaje extends Model
{
    protected $table = 'cliente_perfil_viaje';

    protected $fillable = [
        'id_cliente',
        'typical_travel_group',
        'has_children',
        'children_age_ranges',
        'preferred_occupancy',
        'needs_connected_rooms',
    ];

    protected $casts = [
        'has_children'         => 'bool',
        'children_age_ranges'  => 'array', // JSON <-> array
        'preferred_occupancy'  => 'integer',
        'needs_connected_rooms'=> 'bool',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
