<?php

namespace App\Models\cliente;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';

    // Campos que se pueden asignar en masa
    protected $fillable = [
        'nombre',
        'apellido1',
        'apellido2',
        'email',
        'telefono',
        'id_tipo_doc',       // FK a tipos_documento (si lo usas)
        'numero_doc',
        'nacionalidad',      // almacenado en esta tabla
        'direccion',
        'fecha_nacimiento',
        'genero',
        'es_vip','notas_personal',   // ← IMPORTANTES
    ];

    // Casts útiles
    protected $casts = [
        'id_tipo_doc'       => 'int',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'fecha_nacimiento'  => 'date:Y-m-d',
        'es_vip'            => 'boolean',
    ];

    /* -----------------------------
     |  Accessors / Appended attrs
     ----------------------------- */
    protected $appends = ['nombre_completo'];

    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->nombre,
            $this->apellido1,
            $this->apellido2,
        ])));
    }

    /* -------------
     |  Scopes
     ------------- */
    /**
     * Búsqueda rápida por documento, nombre, apellidos, email o teléfono.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        $like = "%{$term}%";
        return $query->where(function ($q) use ($like) {
            $q->where('numero_doc', 'like', $like)
              ->orWhere('nombre', 'like', $like)
              ->orWhere('apellido1', 'like', $like)
              ->orWhere('apellido2', 'like', $like)
              ->orWhere('email', 'like', $like)
              ->orWhere('telefono', 'like', $like);
        });
    }

    /* -----------------
     |  Relaciones
     ----------------- */
    public function tipoDocumento()
    {
        // Ajusta el namespace/clase si tu modelo de catálogo tiene otro nombre
        return $this->belongsTo(\App\Models\cliente\TipoDoc::class, 'id_tipo_doc', 'id_tipo_doc');
    }

    public function estadiasTitular()
    {
        return $this->hasMany(\App\Models\estadia\Estadia::class, 'id_cliente_titular', 'id_cliente');
    }

    public function reservas()
    {
        return $this->hasMany(\App\Models\reserva\Reserva::class, 'id_cliente', 'id_cliente');
    }

public function preferencias()
{
    return $this->hasOne(\App\Models\cliente\ClientePreferencias::class, 'id_cliente', 'id_cliente');
}
public function perfilViaje()
{
    return $this->hasOne(\App\Models\cliente\ClientePerfilViaje::class, 'id_cliente', 'id_cliente');
}

public function salud()
{
    return $this->hasOne(\App\Models\cliente\ClienteSalud::class, 'id_cliente', 'id_cliente');
}

public function contactoEmergencia() {
    return $this->hasOne(\App\Models\cliente\ClienteContactoEmergencia::class, 'id_cliente', 'id_cliente');
}




}
