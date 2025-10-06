<?php

namespace App\Http\Controllers\Api\clientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\clientes\StoreClienteFullRequest;
use App\Http\Resources\clientes\ClienteResource;
use App\Models\cliente\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request; // ← asegúrate de importar
use Symfony\Component\HttpFoundation\Response;

class ClienteFullController extends Controller
{
    public function store(Request $r) // ← NOTA: usamos Request aquí para poder validar distinto por rama
    {
        /* ----------------------------------------------------------------
         |  RAMA 1: Autenticado → actualizar SIEMPRE al cliente logueado
         ---------------------------------------------------------------- */
        if ($me = $r->user()) {
            // Validación “suave”: solo lo que venga (sometimes)
            $data = $r->validate([
                // base cliente (opcionales)
                'nombre'            => ['sometimes','string','max:100'],
                'apellido1'         => ['sometimes','string','max:100'],
                'apellido2'         => ['sometimes','nullable','string','max:100'],
                'telefono'          => ['sometimes','nullable','string','max:30'],
                'id_tipo_doc'       => ['sometimes','nullable','integer'],
                'numero_doc'        => ['sometimes','nullable','string','max:40'],
                'nacionalidad'      => ['sometimes','nullable','string','max:60'],
                'direccion'         => ['sometimes','nullable','string','max:200'],
                'fecha_nacimiento'  => ['sometimes','nullable','date'],
                'genero'            => ['sometimes','nullable','string','max:1'],
                'es_vip'            => ['sometimes','boolean'],
                'notas_personal'    => ['sometimes','nullable','string'],

                // secciones (opcionales)
                'bed_type'              => ['sometimes','nullable','string','max:50'],
                'floor'                 => ['sometimes','nullable','string','max:50'],
                'view'                  => ['sometimes','nullable','string','max:50'],
                'smoking_allowed'       => ['sometimes','nullable','boolean'],

                'typical_travel_group'  => ['sometimes','nullable','string','max:50'],
                'has_children'          => ['sometimes','nullable','boolean'],
                'children_age_ranges'   => ['sometimes','nullable','string','max:200'],
                'preferred_occupancy'   => ['sometimes','nullable','string','max:50'],
                'needs_connected_rooms' => ['sometimes','nullable','boolean'],

                'allergies'             => ['sometimes','nullable','string','max:500'],
                'dietary_restrictions'  => ['sometimes','nullable','string','max:500'],
                'medical_notes'         => ['sometimes','nullable','string','max:1000'],

                'ec_name'               => ['sometimes','nullable','string','max:150'],
                'ec_relationship'       => ['sometimes','nullable','string','max:50'],
                'ec_phone'              => ['sometimes','nullable','string','max:30'],
                'ec_email'              => ['sometimes','nullable','email','max:150'],
            ]);

            return DB::transaction(function () use ($me, $data) {
                // 1) actualizar base del cliente autenticado (sin tocar email aquí)
                $me->fill(collect($data)->only([
                    'nombre','apellido1','apellido2','telefono','id_tipo_doc','numero_doc',
                    'nacionalidad','direccion','fecha_nacimiento','genero','es_vip','notas_personal',
                ])->toArray());
                $me->save();

                // 2) upsert secciones (hasOne) usando los mismos nombres que ya usabas
                if (array_intersect(array_keys($data), ['bed_type','floor','view','smoking_allowed'])) {
                    $me->preferencias()->firstOrCreate(['id_cliente'=>$me->id_cliente])
                        ->fill([
                            'bed_type'        => $data['bed_type']        ?? null,
                            'floor'           => $data['floor']           ?? null,
                            'view'            => $data['view']            ?? null,
                            'smoking_allowed' => $data['smoking_allowed'] ?? null,
                        ])->save();
                }

                if (array_intersect(array_keys($data), ['typical_travel_group','has_children','children_age_ranges','preferred_occupancy','needs_connected_rooms'])) {
                    $me->perfilViaje()->firstOrCreate(['id_cliente'=>$me->id_cliente])
                        ->fill([
                            'typical_travel_group' => $data['typical_travel_group'] ?? null,
                            'has_children'         => $data['has_children'] ?? null,
                            'children_age_ranges'  => $data['children_age_ranges'] ?? null,
                            'preferred_occupancy'  => $data['preferred_occupancy'] ?? null,
                            'needs_connected_rooms'=> $data['needs_connected_rooms'] ?? null,
                        ])->save();
                }

                if (array_intersect(array_keys($data), ['allergies','dietary_restrictions','medical_notes'])) {
                    $me->salud()->firstOrCreate(['id_cliente'=>$me->id_cliente])
                        ->fill([
                            'allergies'            => $data['allergies'] ?? null,
                            'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
                            'medical_notes'        => $data['medical_notes'] ?? null,
                        ])->save();
                }

                if (array_intersect(array_keys($data), ['ec_name','ec_relationship','ec_phone','ec_email'])) {
                    $me->contactoEmergencia()->firstOrCreate(['id_cliente'=>$me->id_cliente])
                        ->fill([
                            'name'         => $data['ec_name'] ?? null,
                            'relationship' => $data['ec_relationship'] ?? null,
                            'phone'        => $data['ec_phone'] ?? null,
                            'email'        => $data['ec_email'] ?? null,
                        ])->save();
                }

                return response()->json([
                    'message' => 'Perfil actualizado',
                    'cliente' => $me->fresh(['preferencias','perfilViaje','salud','contactoEmergencia']),
                ], Response::HTTP_OK);
            });
        }

        /* ----------------------------------------------------------------
         |  RAMA 2: Público → tu lógica idempotente (create-or-update)
         |  (SIN token) → se mantiene EXACTAMENTE como la tenías
         ---------------------------------------------------------------- */
        // Aquí sí usamos tu FormRequest existente
        $data = app(StoreClienteFullRequest::class)->validated();

        $email     = isset($data['email']) ? mb_strtolower(trim($data['email'])) : null;
        $numeroDoc = isset($data['numero_doc']) ? preg_replace('/[\s\-]/', '', $data['numero_doc']) : null;

        try {
            [$cliente, $created] = DB::transaction(function () use ($data, $email, $numeroDoc) {
                $existing = Cliente::query()
                    ->when($email, fn($q) => $q->where('email', $email))
                    ->when($numeroDoc, fn($q) => $q->orWhere('numero_doc', $numeroDoc))
                    ->first();

                if (!$existing) {
                    $cliente = Cliente::create([
                        'nombre'           => $data['nombre'],
                        'apellido1'        => $data['apellido1'],
                        'apellido2'        => $data['apellido2'] ?? null,
                        'email'            => $email,
                        'telefono'         => $data['telefono'] ?? null,
                        'id_tipo_doc'      => $data['id_tipo_doc'] ?? null,
                        'numero_doc'       => $numeroDoc,
                        'nacionalidad'     => $data['nacionalidad'] ?? null,
                        'direccion'        => $data['direccion'] ?? null,
                        'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                        'genero'           => $data['genero'] ?? null,
                        'es_vip'           => $data['es_vip'] ?? false,
                        'notas_personal'   => $data['notas_personal'] ?? null,
                    ]);
                    $created = true;
                } else {
                    $update = array_filter([
                        'nombre'           => $data['nombre']            ?? null,
                        'apellido1'        => $data['apellido1']         ?? null,
                        'apellido2'        => $data['apellido2']         ?? null,
                        'telefono'         => $data['telefono']          ?? null,
                        'id_tipo_doc'      => $data['id_tipo_doc']       ?? null,
                        'numero_doc'       => $numeroDoc,
                        'nacionalidad'     => $data['nacionalidad']      ?? null,
                        'direccion'        => $data['direccion']         ?? null,
                        'fecha_nacimiento' => $data['fecha_nacimiento']  ?? null,
                        'genero'           => $data['genero']            ?? null,
                        'es_vip'           => $data['es_vip']            ?? null,
                        'notas_personal'   => $data['notas_personal']    ?? null,
                        'email'            => $email,
                    ], fn($v) => !is_null($v));

                    if (isset($update['email'])) {
                        validator(['email' => $update['email']], [
                            'email' => ['email','max:150', Rule::unique('clientes','email')->ignore($existing->id_cliente,'id_cliente')],
                        ])->validate();
                    }

                    $existing->fill($update)->save();
                    $cliente = $existing;
                    $created = false;
                }

                // Secciones (igual que ya tenías)
                if (!empty($data['bed_type']) || !empty($data['floor']) || !empty($data['view']) || array_key_exists('smoking_allowed',$data)) {
                    $cliente->preferencias()->updateOrCreate(
                        ['id_cliente' => $cliente->id_cliente],
                        [
                            'bed_type'        => $data['bed_type'] ?? null,
                            'floor'           => $data['floor'] ?? null,
                            'view'            => $data['view'] ?? null,
                            'smoking_allowed' => $data['smoking_allowed'] ?? null,
                        ]
                    );
                }

                if (!empty($data['typical_travel_group']) || array_key_exists('has_children',$data) || !empty($data['children_age_ranges']) || !empty($data['preferred_occupancy']) || array_key_exists('needs_connected_rooms',$data)) {
                    $cliente->perfilViaje()->updateOrCreate(
                        ['id_cliente' => $cliente->id_cliente],
                        [
                            'typical_travel_group' => $data['typical_travel_group'] ?? null,
                            'has_children'         => $data['has_children'] ?? null,
                            'children_age_ranges'  => $data['children_age_ranges'] ?? null,
                            'preferred_occupancy'  => $data['preferred_occupancy'] ?? null,
                            'needs_connected_rooms'=> $data['needs_connected_rooms'] ?? null,
                        ]
                    );
                }

                if (!empty($data['allergies']) || !empty($data['dietary_restrictions']) || !empty($data['medical_notes'])) {
                    $cliente->salud()->updateOrCreate(
                        ['id_cliente' => $cliente->id_cliente],
                        [
                            'allergies'            => $data['allergies'] ?? null,
                            'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
                            'medical_notes'        => $data['medical_notes'] ?? null,
                        ]
                    );
                }

                if (!empty($data['ec_name']) || !empty($data['ec_relationship']) || !empty($data['ec_phone']) || !empty($data['ec_email'])) {
                    $cliente->contactoEmergencia()->updateOrCreate(
                        ['id_cliente' => $cliente->id_cliente],
                        [
                            'name'         => $data['ec_name'] ?? null,
                            'relationship' => $data['ec_relationship'] ?? null,
                            'phone'        => $data['ec_phone'] ?? null,
                            'email'        => $data['ec_email'] ?? null,
                        ]
                    );
                }

                return [$cliente->fresh()->load(['tipoDocumento','preferencias','perfilViaje','salud','contactoEmergencia']), $created];
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Conflicto: documento, email o teléfono ya existe.'], Response::HTTP_CONFLICT);
            }
            throw $e;
        }

        $status = $created ? Response::HTTP_CREATED : Response::HTTP_OK;
        return (new ClienteResource($cliente))->response()->setStatusCode($status);
    }
}

/*namespace App\Http\Controllers\Api\clientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\clientes\StoreClienteFullRequest;
use App\Http\Resources\clientes\ClienteResource;
use App\Models\cliente\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class ClienteFullController extends Controller
{
    public function store(StoreClienteFullRequest $r)
    {
        $data = $r->validated();

        try {
            $cliente = DB::transaction(function () use ($data) {
                // 1) Cliente
                $cliente = Cliente::create([
                    'nombre'           => $data['nombre'],
                    'apellido1'        => $data['apellido1'],
                    'apellido2'        => $data['apellido2'] ?? null,
                    'email'            => $data['email'],
                    'telefono'         => $data['telefono'],
                    'id_tipo_doc'      => $data['id_tipo_doc'] ?? null,
                    'numero_doc'       => $data['numero_doc'] ?? null,
                    'nacionalidad'     => $data['nacionalidad'],
                    'direccion'        => $data['direccion'] ?? null,
                    'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                    'genero'           => $data['genero'] ?? null,
                    'es_vip'           => $data['es_vip'] ?? false,
                    'notas_personal'   => $data['notas_personal'] ?? null,
                ]);

                // 2) Preferencias (si hay algo)
                if (!empty($data['bed_type']) || !empty($data['floor']) || !empty($data['view']) || array_key_exists('smoking_allowed', $data)) {
                    $cliente->preferencias()->create([
                        'bed_type'        => $data['bed_type'] ?? null,
                        'floor'           => $data['floor'] ?? null,
                        'view'            => $data['view'] ?? null,
                        'smoking_allowed' => $data['smoking_allowed'] ?? null,
                    ]);
                }

                // 3) Perfil de viaje
                if (!empty($data['typical_travel_group']) || array_key_exists('has_children',$data) || !empty($data['children_age_ranges']) || !empty($data['preferred_occupancy']) || array_key_exists('needs_connected_rooms',$data)) {
                    $cliente->perfilViaje()->create([
                        'typical_travel_group' => $data['typical_travel_group'] ?? null,
                        'has_children'         => $data['has_children'] ?? null,
                        'children_age_ranges'  => $data['children_age_ranges'] ?? null,
                        'preferred_occupancy'  => $data['preferred_occupancy'] ?? null,
                        'needs_connected_rooms'=> $data['needs_connected_rooms'] ?? null,
                    ]);
                }

                // 4) Salud
                if (!empty($data['allergies']) || !empty($data['dietary_restrictions']) || !empty($data['medical_notes'])) {
                    $cliente->salud()->create([
                        'allergies'            => $data['allergies'] ?? null,
                        'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
                        'medical_notes'        => $data['medical_notes'] ?? null,
                    ]);
                }

                // 5) Emergencia
                if (!empty($data['ec_name']) || !empty($data['ec_relationship']) || !empty($data['ec_phone']) || !empty($data['ec_email'])) {
                    $cliente->contactoEmergencia()->create([
                        'name'        => $data['ec_name'] ?? null,
                        'relationship'=> $data['ec_relationship'] ?? null,
                        'phone'       => $data['ec_phone'] ?? null,
                        'email'       => $data['ec_email'] ?? null,
                    ]);
                }

                return $cliente->fresh()->load(['tipoDocumento','preferencias','perfilViaje','salud','contactoEmergencia']);
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Conflicto: documento, email o teléfono ya existe.'], Response::HTTP_CONFLICT);
            }
            throw $e;
        }

        return (new ClienteResource($cliente))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
*/
