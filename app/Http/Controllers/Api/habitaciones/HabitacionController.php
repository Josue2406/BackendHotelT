<?php
namespace App\Http\Controllers\Api\habitaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\habitaciones\StoreHabitacionRequest;
use App\Http\Requests\habitaciones\UpdateHabitacionRequest;
use App\Models\habitacion\Habitacione;
use App\Services\house_keeping\RegistroAutomaticoDeLimpiezaService;
class HabitacionController extends Controller
{
    protected RegistroAutomaticoDeLimpiezaService $registroLimpieza;

    public function __construct(RegistroAutomaticoDeLimpiezaService $registroLimpieza)
    {
        $this->registroLimpieza = $registroLimpieza;
    }
    public function index() { return Habitacione::with(['estado','tipo'])->orderBy('numero')->paginate(20); }
    public function show(Habitacione $habitacione) { return $habitacione->load(['estado','tipo']); }

    // public function store(StoreHabitacionRequest $r) {
    //     return response()->json(Habitacione::create($r->validated()), 201);

    // }
public function store(StoreHabitacionRequest $r)
{
    // Crear la habitación normalmente
    $habitacion = Habitacione::create($r->validated());

    // Crear automáticamente el registro de limpieza vacío asociado a esta habitación
    $this->registroLimpieza->crearDesdeNuevaHabitacion($habitacion->id_habitacion);

    return response()->json($habitacion, 201);
}
    public function update(UpdateHabitacionRequest $r, Habitacione $habitacione) {
        $habitacione->update($r->validated());
        return $habitacione->fresh();
    }
}
