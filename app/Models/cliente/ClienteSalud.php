<?php
namespace App\Models\cliente;

use Illuminate\Database\Eloquent\Model;

class ClienteSalud extends Model
{
    protected $table = 'cliente_salud';

    protected $fillable = [
        'id_cliente',
        'allergies',
        'dietary_restrictions',
        'medical_notes',
    ];

    protected $casts = [
        'allergies'            => 'array',
        'dietary_restrictions' => 'array',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
