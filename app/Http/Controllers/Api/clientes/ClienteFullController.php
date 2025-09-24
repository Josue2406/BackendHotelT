<?php
namespace App\Http\Controllers\Api\clientes;

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
