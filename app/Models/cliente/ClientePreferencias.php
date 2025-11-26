<?php
namespace App\Models\cliente;

use Illuminate\Database\Eloquent\Model;

class ClientePreferencias extends Model
{
    protected $table = 'cliente_preferencias';

    protected $fillable = [
        'id_cliente',
        'bed_type',        // single|double|queen|king|twin
        'floor',           // low|middle|high
        'view',            // ocean|mountain|city|garden
        'smoking_allowed', // bool
    ];

    protected $casts = [
        'smoking_allowed' => 'bool',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
