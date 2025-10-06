
<?php
use App\Http\Controllers\Api\frontdesk\FrontDeskController; //Ruta nueva

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\usuario\RolController;
use App\Http\Controllers\Api\usuario\UsuarioController;
use App\Http\Controllers\Api\house_keeping\LimpiezaController;
use App\Http\Controllers\Api\house_keeping\MantenimientoController;
use App\Http\Controllers\Api\catalogo\EstadoHabitacionController;
use App\Http\Controllers\Api\catalogo\TipoHabitacionController;
use App\Http\Controllers\Api\catalogo\AmenidadController;
use App\Http\Controllers\Api\catalogo\HabitacionAmenidadController;
use App\Http\Controllers\Api\catalogo\FuenteController;
use App\Http\Controllers\Api\catalogo\TipoDocController;
use App\Http\Controllers\Api\catalogo\EstadoReservaController;
use App\Http\Controllers\Api\Clientes\ClienteController;
use App\Http\Controllers\Api\habitaciones\HabitacionController;
use App\Http\Controllers\Api\habitaciones\BloqueoOperativoController;
use App\Http\Controllers\Api\habitaciones\DisponibilidadController;
use App\Http\Controllers\Api\reserva\{
  ReservaController, ReservaHabitacionController, ReservaServicioController, ReservaPoliticaController
};

use App\Http\Controllers\Api\frontdesk\WalkInsController;
use App\Http\Controllers\Api\frontdesk\ReservasCheckinController;
use App\Http\Controllers\Api\frontdesk\EstadoEstadiaController;
use App\Http\Controllers\Api\frontdesk\EstadiasController;

use App\Http\Controllers\Api\clientes\ClienteWizardController;
use App\Http\Controllers\Api\Auth\AuthController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

//use App\Http\Controllers\Api\frontdesk\AsignacionHabitacion;

//Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('limpiezas', LimpiezaController::class);
    Route::apiResource('mantenimientos', MantenimientoController::class);
//});
Route::apiResource('roles', RolController::class);
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('estados-habitacion', EstadoHabitacionController::class);
Route::apiResource('tipos-habitacion', TipoHabitacionController::class);
Route::apiResource('amenidades', AmenidadController::class);
Route::apiResource('habitacion-amenidad',HabitacionAmenidadController::class)->only(['index','show','store','destroy']);
Route::apiResource('fuentes', FuenteController::class);
Route::apiResource('tipos-doc', TipoDocController::class);
Route::apiResource('estados-reserva', EstadoReservaController::class);
//Route::apiResource('clientes', ClienteController::class);
Route::apiResource('habitaciones', HabitacionController::class)->only(['index','show','store','update']);
Route::apiResource('bloqueos', BloqueoOperativoController::class)->only(['index','show','store','destroy']);

Route::get('disponibilidad', DisponibilidadController::class);


// CRUD reserva
Route::apiResource('reservas', ReservaController::class);

// Habitaciones por reserva
Route::get('reservas/{reserva}/habitaciones',         [ReservaHabitacionController::class, 'index']);
Route::post('reservas/{reserva}/habitaciones',        [ReservaHabitacionController::class, 'store']);
Route::put('reservas/{reserva}/habitaciones/{id}',    [ReservaHabitacionController::class, 'update']);
Route::delete('reservas/{reserva}/habitaciones/{id}', [ReservaHabitacionController::class, 'destroy']);

// Servicios por reserva
Route::get('reservas/{reserva}/servicios',         [ReservaServicioController::class, 'index']);
Route::post('reservas/{reserva}/servicios',        [ReservaServicioController::class, 'store']);
Route::put('reservas/{reserva}/servicios/{id}',    [ReservaServicioController::class, 'update']);
Route::delete('reservas/{reserva}/servicios/{id}', [ReservaServicioController::class, 'destroy']);

// Políticas por reserva
Route::get('reservas/{reserva}/politicas',         [ReservaPoliticaController::class, 'index']);
Route::post('reservas/{reserva}/politicas',        [ReservaPoliticaController::class, 'store']);
Route::delete('reservas/{reserva}/politicas/{id}', [ReservaPoliticaController::class, 'destroy']);

// Acciones de reserva
Route::post('reservas/{reserva}/confirmar', [ReservaController::class, 'confirmar']);
Route::post('reservas/{reserva}/cancelar',  [ReservaController::class, 'cancelar']);
Route::post('reservas/{reserva}/cotizar',   [ReservaController::class, 'cotizar']);
Route::post('reservas/{reserva}/no-show',   [ReservaController::class, 'noShow']);
Route::post('reservas/{reserva}/checkin',   [ReservaController::class, 'generarEstadia']);














/*
Walk-in (crear estadía + asignación + check-in)

Check-in desde reserva (genera estadía + asignación + check-in)

Cambio de habitación (room move)

Ajuste de fechas de la estadía

Check-out (crea evento)
*/

Route::prefix('frontdesk')->group(function () {
    // Walk-in
    Route::post('/walkin', [WalkInsController::class, 'store']);

    // Check-in desde reserva
    Route::post('/reserva/{reserva}/checkin', [ReservasCheckinController::class, 'store']);
    //Route::post('/frontdesk/reserva/{reserva}/checkin', [ReservasCheckinController::class, 'store']);

    // Operaciones de estadía
   // Route::post('/estadia/{estadia}/room-move', [EstadiasController::class, 'roomMove']);
   // Route::patch('/estadia/{estadia}/fechas',   [EstadiasController::class, 'updateFechas']);
    //Route::post('/estadia/{estadia}/checkout',  [EstadiasController::class, 'checkout']);

    // Consultas
    //Route::get('/estadia/{estadia}', [EstadiasController::class, 'show']);
    //Route::get('/estadias',          [EstadiasController::class, 'index']);

    
    //Estado de estadia
    Route::get('/estado-estadia',  [EstadoEstadiaController::class, 'index']);
    Route::post('/estado-estadia', [EstadoEstadiaController::class, 'store']);



    //Estadias
     Route::get('/estadia/{estadia}', [EstadiasController::class, 'show']);   // detalle
  Route::get('/estadias',          [EstadiasController::class, 'index']);  // listado/paginado


  Route::post('/estadia/{estadia}/room-move', [EstadiasController::class, 'roomMove']);
  Route::patch('/estadia/{estadia}/fechas',    [EstadiasController::class, 'updateFechas']);
  Route::post('/estadia/{estadia}/checkout',  [EstadiasController::class, 'checkout']);
  Route::get('/estadia/{estadia}',            [EstadiasController::class, 'show']);
  Route::get('/estadias',                     [EstadiasController::class, 'index']);
});


Route::prefix('clientes')->group(function () {
    Route::get('/',            [ClienteController::class, 'index']);
    Route::post('/',           [ClienteController::class, 'store']);
    Route::get('{cliente}',    [ClienteController::class, 'show']);
    Route::patch('{cliente}',    [ClienteController::class, 'update']);
    Route::delete('{cliente}', [ClienteController::class, 'destroy']);

    Route::get('por-doc/{numero_doc}',    [ClienteController::class, 'findByDocumento']);
    Route::get('exists-doc/{numero_doc}', [ClienteController::class, 'existsByDocumento']);
    Route::post('upsert-por-doc',         [ClienteController::class, 'upsertByDocumento']);
});


Route::prefix('clientes/{cliente}/wizard')
    ->name('clientes.wizard.')
    ->group(function () {
        Route::patch('habitacion',   [ClienteWizardController::class, 'habitacion'])->name('habitacion');
        Route::patch('perfil-viaje', [ClienteWizardController::class, 'perfilViaje'])->name('perfil_viaje');
        Route::patch('salud',        [ClienteWizardController::class, 'salud'])->name('salud');
        Route::patch('emergencia',   [ClienteWizardController::class, 'emergencia'])->name('emergencia');
        Route::get('progreso',       [ClienteWizardController::class, 'progreso'])->name('progreso');
    });