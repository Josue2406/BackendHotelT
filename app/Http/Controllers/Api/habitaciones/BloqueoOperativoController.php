<?php
namespace App\Http\Controllers\Api\habitaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBloqueoOperativoRequest;
use App\Models\HabBloqueoOperativo;

class BloqueoOperativoController extends Controller
{
    public function index() { return HabBloqueoOperativo::with('habitacion')->latest('id_bloqueo')->paginate(20); }
    public function show(HabBloqueoOperativo $bloqueo) { return $bloqueo->load('habitacion'); }

    public function store(StoreBloqueoOperativoRequest $r) {
        return response()->json(HabBloqueoOperativo::create($r->validated()), 201);
    }

    public function destroy(HabBloqueoOperativo $bloqueo) {
        $bloqueo->delete();
        return response()->noContent();
    }
}
