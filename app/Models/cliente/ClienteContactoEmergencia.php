<?php
namespace App\Models\cliente;

use Illuminate\Database\Eloquent\Model;

class ClienteContactoEmergencia extends Model
{
    protected $table = 'cliente_contacto_emergencia';
    protected $fillable = ['id_cliente','name','relationship','phone','email'];

    public function cliente() {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
