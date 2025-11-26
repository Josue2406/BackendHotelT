<?php
namespace App\Http\Controllers\Api\clientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\clientes\wizard\UpdatePreferenciasRequest;
use App\Http\Requests\clientes\wizard\UpdatePerfilViajeRequest;
use App\Http\Requests\clientes\wizard\UpdateSaludRequest;
use App\Http\Requests\clientes\wizard\UpdateEmergenciaRequest;
use App\Http\Resources\clientes\ClienteResource;
use App\Models\cliente\Cliente;

class ClienteWizardController extends Controller
{
    
    public function habitacion(
    \App\Http\Requests\clientes\wizard\UpdatePreferenciasRequest $r,
    \App\Models\cliente\Cliente $cliente
) {
    $data = $r->validated();

    $record = $cliente->preferencias()->first();
    $record ? $record->fill($data)->save() : $cliente->preferencias()->create($data);

    // Devuelve el objeto cargado para que el FE pinte el resumen de paso
    return response()->json([
        'message' => 'Preferencias de habitación guardadas',
        'preferencias' => $cliente->preferencias()->first(),
    ]);
}

   public function perfilViaje(UpdatePerfilViajeRequest $r, \App\Models\cliente\Cliente $cliente)
{
    $data = $r->validated();

    $record = $cliente->perfilViaje()->first();
    $record ? $record->fill($data)->save() : $cliente->perfilViaje()->create($data);

    return response()->json([
        'message' => 'Perfil de viaje guardado',
        'perfil_viaje' => $cliente->perfilViaje()->first(),
    ]);
}

   public function salud(UpdateSaludRequest $r, \App\Models\cliente\Cliente $cliente)
{
    $data = $r->validated();

    $record = $cliente->salud()->first();
    $record ? $record->fill($data)->save() : $cliente->salud()->create($data);

    return response()->json([
        'message' => 'Información médica guardada',
        'salud'   => $cliente->salud()->first(),
    ]);
}

   public function emergencia(UpdateEmergenciaRequest $r, \App\Models\cliente\Cliente $cliente)
{
    $data = $r->validated();

    $record = $cliente->contactoEmergencia()->first();
    $record ? $record->fill($data)->save() : $cliente->contactoEmergencia()->create($data);

    return response()->json([
        'message' => 'Contacto de emergencia guardado',
        'emergency_contact' => $cliente->contactoEmergencia()->first(),
    ]);
}


public function progreso(Cliente $cliente)
{
    $cliente->load(['preferencias','perfilViaje','salud','contactoEmergencia']);

    // Paso 1: Datos básicos (los que tu UI marca requeridos)
    $paso1 = filled($cliente->nombre)
          && filled($cliente->apellido1)
          && filled($cliente->email)
          && filled($cliente->telefono)
          && filled($cliente->nacionalidad)
          && filled($cliente->id_tipo_doc)
          && filled($cliente->numero_doc);

    // Paso 2: Información adicional (considera hecho si hay algo cargado)
    $paso2 = filled($cliente->fecha_nacimiento)
          || filled($cliente->genero)
          || filled($cliente->es_vip)
          || filled($cliente->notas_personal)
          || filled($cliente->direccion);

    // Helpers para “hay algo útil” en cada relación
    $hasAny = function ($model, array $fields) {
        if (!$model) return false;
        foreach ($fields as $f) {
            if (filled(data_get($model, $f))) return true;
        }
        return false;
    };

    // Paso 3: Habitación
    $paso3 = $hasAny($cliente->preferencias, [
        'bed_type','floor','view','smoking_allowed'
    ]);

    // Paso 4: Perfil de viaje
    $paso4 = $hasAny($cliente->perfilViaje, [
        'typical_travel_group','has_children','children_age_ranges','preferred_occupancy','needs_connected_rooms'
    ]);

    // Paso 5: Salud
    $paso5 = $hasAny($cliente->salud, [
        'allergies','dietary_restrictions','medical_notes'
    ]);

    // Paso 6: Emergencia (OJO a los nombres: name/phone/email/relationship)
    $paso6 = $hasAny($cliente->contactoEmergencia, [
        'name','phone','email','relationship'
    ]);

    $steps = [
        ['key' => 'datos',        'done' => $paso1],
        ['key' => 'informacion',  'done' => $paso2],
        ['key' => 'habitacion',   'done' => $paso3],
        ['key' => 'perfil_viaje', 'done' => $paso4],
        ['key' => 'salud',        'done' => $paso5],
        ['key' => 'emergencia',   'done' => $paso6],
    ];

    $done = collect($steps)->where('done', true)->count();

    return [
        'cliente_id' => $cliente->id_cliente,
        'steps'      => $steps,
        'current'    => "Paso {$done} de 6",
        'complete'   => $done === 6,
        'percent'    => round($done * 100 / 6), // opcional
    ];
}

}
