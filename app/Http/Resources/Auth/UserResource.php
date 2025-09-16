<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_usuario' => $this->id_usuario,
            'nombre'     => $this->nombre,
            'apellido1'  => $this->apellido1,
            'apellido2'  => $this->apellido2,
            'email'      => $this->email,
            'rol'        => $this->rol?->nombre,
        ];
    }
}