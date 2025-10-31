
<?php
use App\Http\Controllers\Api\frontdesk\FrontDeskController; //Ruta nueva
use App\Http\Controllers\Api\PagoController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\usuario\RolController;
use App\Http\Controllers\Api\usuario\UsuarioController;
use App\Http\Controllers\Api\house_keeping\LimpiezaController;
use App\Http\Controllers\Api\house_keeping\MantenimientoController;
use App\Http\Controllers\Api\house_keeping\HistorialLimpiezaController;
use App\Http\Controllers\Api\house_keeping\HistorialMantenimientoController;
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
  ReservaController, ReservaHabitacionController, ReservaServicioController, ReservaPoliticaController, ServicioController, ReporteController
};


use App\Http\Controllers\Api\frontdesk\WalkinController;
use App\Http\Controllers\Api\frontdesk\ReservasCheckinController;
use App\Http\Controllers\Api\frontdesk\EstadoEstadiaController;
use App\Http\Controllers\Api\frontdesk\EstadiasController;
use App\Http\Controllers\Api\reserva\AvailabilityController;
use App\Http\Controllers\Api\reserva\TemporadaController;
use App\Http\Controllers\Api\reserva\TemporadaReglaController;


use App\Http\Controllers\Api\clientes\ClienteWizardController;
use App\Http\Controllers\Api\clientes\ClienteFullController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;

use App\Http\Controllers\Api\Auth\AuthController;

use App\Http\Controllers\Api\Clientes\AuthClienteController;
use App\Http\Controllers\Api\Clientes\PasswordResetClienteController;
use App\Http\Controllers\Api\Huespedes\HuespedController;   // ← IMPORTANTE
use App\Http\Controllers\Api\Clientes\ClienteIntakeController; // ← si usarás perfil-completo

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendCode']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

//use App\Http\Controllers\Api\frontdesk\AsignacionHabitacion;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('limpiezas', LimpiezaController::class);
    Route::apiResource('mantenimientos', MantenimientoController::class);
});
Route::apiResource('historial-limpiezas', HistorialLimpiezaController::class)
    ->only(['index', 'show']);

// Historial por limpieza específica
Route::get('limpiezas/{id}/historial', [HistorialLimpiezaController::class, 'porLimpieza']);

Route::apiResource('historial-mantenimientos', HistorialMantenimientoController::class)
    ->only(['index', 'show']);

Route::get('mantenimientos/{id}/historial', [HistorialMantenimientoController::class, 'porMantenimiento']);

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
Route::get('availability/search', [AvailabilityController::class, 'search']);

// Búsqueda por Código de Reserva (DEBE IR ANTES DE apiResource para evitar conflictos)
Route::get('reservas/buscar', [ReservaController::class, 'buscarPorCodigo']);
Route::get('reservas/codigos/estadisticas', [ReservaController::class, 'estadisticasCodigos']);

// CRUD reserva
// POST (store) sin autenticación obligatoria para permitir reservas desde recepción
Route::post('reservas', [ReservaController::class, 'store']); // Web (con token) o Recepción (sin token)

// El resto de operaciones CRUD requieren autenticación
Route::middleware('auth:sanctum')->group(function () {
    Route::get('reservas', [ReservaController::class, 'index']);
    Route::get('reservas/{reserva}', [ReservaController::class, 'show']);
    Route::put('reservas/{reserva}', [ReservaController::class, 'update']);
    Route::patch('reservas/{reserva}', [ReservaController::class, 'update']);
    Route::delete('reservas/{reserva}', [ReservaController::class, 'destroy']);
});

Route::apiResource('temporadas', TemporadaController::class);

Route::apiResource('temporada-reglas', TemporadaReglaController::class);

// CRUD servicios (catálogo de servicios)
Route::apiResource('servicios', ServicioController::class);


// Habitaciones por reserva
Route::get('reservas/{reserva}/habitaciones',                  [ReservaHabitacionController::class, 'index']);
Route::post('reservas/{reserva}/habitaciones',                 [ReservaHabitacionController::class, 'store']);
Route::put('reservas/{reserva}/habitaciones/{habitacion_id}',  [ReservaHabitacionController::class, 'update'])->where('habitacion_id', '[0-9]+');
Route::delete('reservas/{reserva}/habitaciones/{habitacion_id}', [ReservaHabitacionController::class, 'destroy'])->where('habitacion_id', '[0-9]+');

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
//Route::middleware('auth:sanctum')->group(function () {


Route::post('reservas/{reserva}/confirmar', [ReservaController::class, 'confirmar']);
Route::post('reservas/{reserva}/cancelar',  [ReservaController::class, 'cancelar']);
Route::post('reservas/{reserva}/cotizar',   [ReservaController::class, 'cotizar']);
Route::post('reservas/{reserva}/no-show',   [ReservaController::class, 'noShow']);
Route::post('reservas/{reserva}/checkin',   [ReservaController::class, 'generarEstadia']);
Route::post('reservas/{reserva}/check-in',  [ReservaController::class, 'checkIn']);
Route::put('reservas/{reserva}/estado',     [ReservaController::class, 'cambiarEstado']);
Route::post('reservas/{reserva}/realizar-checkin',  [ReservaController::class, 'realizarCheckIn']);
Route::post('reservas/{reserva}/realizar-checkout', [ReservaController::class, 'realizarCheckOut']);


// Sistema de Pagos (Existentes)
Route::post('reservas/{reserva}/pagos', [ReservaController::class, 'procesarPago']);
Route::get('reservas/{reserva}/pagos', [ReservaController::class, 'listarPagos']);

// ========== NUEVOS ENDPOINTS DE PAGOS - HOTEL LANAKU ==========
// Procesamiento de pagos con múltiples divisas y conversión automática
Route::post('pagos/inicial', [PagoController::class, 'pagoInicial']);          // Pago inicial 30%
Route::post('pagos/restante', [PagoController::class, 'pagoRestante']);        // Pago saldo restante
Route::post('pagos/completo', [PagoController::class, 'pagoCompleto']);        // Pago completo 100%

// Endpoints auxiliares para el sistema de pagos
Route::get('pagos/divisas-principales', [PagoController::class, 'divisasPrincipales']);  // USD, CRC, EUR
Route::get('pagos/metodos', [PagoController::class, 'metodosPago']);                    // Métodos activos
Route::get('pagos/tipo-cambio/{moneda}', [PagoController::class, 'tipoCambio']);        // Tipo de cambio actual
Route::post('pagos/calcular-precio', [PagoController::class, 'calcularPrecio']);        // Calcular en 3 divisas
// ================================================================

// Sistema de Cancelación con Políticas
Route::get('reservas/{reserva}/cancelacion/preview', [ReservaController::class, 'previewCancelacion']);
Route::post('reservas/{reserva}/cancelar-con-politica', [ReservaController::class, 'cancelarConPolitica']);

// Sistema de Extensión de Estadía
Route::post('reservas/{reserva}/extender', [ReservaController::class, 'extenderEstadia']);
Route::post('reservas/{reserva}/extender/confirmar', [ReservaController::class, 'confirmarExtensionCambioHabitacion']);

// Sistema de Modificación de Reservas
Route::post('reservas/{reserva}/modificar/cambiar-habitacion', [ReservaController::class, 'cambiarHabitacion']);
Route::post('reservas/{reserva}/modificar/cambiar-fechas', [ReservaController::class, 'modificarFechas']);
Route::post('reservas/{reserva}/modificar/reducir-estadia', [ReservaController::class, 'reducirEstadia']);

// Sistema de Monedas y Tipos de Cambio
Route::get('monedas/soportadas', [ReservaController::class, 'monedasSoportadas']);
Route::get('monedas/tipos-cambio', [ReservaController::class, 'tiposDeCambio']);
Route::get('monedas/convertir', [ReservaController::class, 'convertirMoneda']);

// Sistema de Reportes y Estadísticas
Route::middleware('auth:sanctum')->prefix('reservas/reportes')->group(function () {
    Route::get('kpis', [ReporteController::class, 'kpis']);
    Route::get('series-temporales', [ReporteController::class, 'seriesTemporales']);
    Route::get('distribuciones', [ReporteController::class, 'distribuciones']);
    Route::get('export/pdf', [ReporteController::class, 'exportPdf']);
});

//});












/*
Walk-in (crear estadía + asignación + check-in)

Check-in desde reserva (genera estadía + asignación + check-in)

Cambio de habitación (room move)

Ajuste de fechas de la estadía

Check-out (crea evento)
*/

Route::prefix('frontdesk')->group(function () {
    // Walk-in
    Route::post('/walkin', [WalkInController::class, 'store']);

//Route::post('/frontdesk/walkin', [WalkinController::class, 'store']);

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

/*
Route::prefix('clientes/full')
    ->name('clientes.full.')
    ->group(function () {
        Route::post('/', [ClienteFullController::class, 'store'])->name('store');
        Route::put('{cliente}', [ClienteFullController::class, 'update'])->name('update');
 });
        */
        Route::prefix('clientes/full')
        ->group(function () {
    Route::post('/', [ClienteFullController::class, 'store']);   // idempotente
    Route::put('{cliente}', [ClienteFullController::class, 'update']); // si lo usas
});

        // Agrega más rutas si es necesario (update, show, etc.)
//Route::post('clientes/full', [ClienteFullController::class, 'store']);


//-------------------------------------------FOLIO-------------------------------------------------
use App\Http\Controllers\Api\folio\FolioResumenController;
Route::get('/folios/{id}/resumen', [FolioResumenController::class, 'show']);

use App\Http\Controllers\Api\folio\FolioDistribucionController;

Route::post('/folios/{id}/distribuir', [FolioDistribucionController::class, 'distribuir']);

use App\Http\Controllers\Api\folio\FolioPagosController;

Route::post('/folios/{id}/pagos', [FolioPagosController::class, 'store']);

use App\Http\Controllers\Api\folio\FolioCierreController;

Route::post('/folios/{id}/cerrar', [FolioCierreController::class, 'cerrar']);

use App\Http\Controllers\Api\folio\FolioHistorialController;

Route::get('/folios/{id}/historial', [FolioHistorialController::class, 'index']);
//-------------------------------------------------------------------------------------------------

use App\Http\Controllers\Api\frontdesk\ClientesLookupController;

Route::get('/frontdesk/clientes/_lookup', [ClientesLookupController::class, 'show'])
    ->name('frontdesk.clientes.lookup');



// routes/api.php



Route::prefix('clientes')->group(function () {
    // Registro + Login
    Route::post('auth/register', [AuthClienteController::class, 'register']);
   /* Route::post('auth/login',    [AuthClienteController::class, 'login'])
    ->middleware('throttle:login');
    */

    Route::post('auth/login', [AuthClienteController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 intentos x 1 minuto por IP

    
// Crear huésped (independiente del cliente)
Route::post('huespedes', [HuespedController::class, 'store']);

// Login con draft (ya lo tienes)
    //Route::post('clientes/auth/login', [AuthClienteController::class, 'login']);

    // Password reset (enviar correo y resetear)
    Route::post('password/forgot', [PasswordResetClienteController::class, 'sendResetLink']);
    Route::post('password/reset',  [PasswordResetClienteController::class, 'resetPassword']);

    // Rutas protegidas por token
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me',     [AuthClienteController::class, 'me']);
        Route::post('auth/logout',[AuthClienteController::class, 'logout']);
        // aquí puedes añadir más endpoints del cliente autenticado


    /* routes/api.php
Route::middleware('auth:sanctum')->prefix('clientes')->group(function () {
    Route::post('full', [ClienteFullController::class, 'store']); // ahora “store” = update del autenticado
});*/
Route::middleware('auth:sanctum')->post('clientes/full', [ClienteFullController::class,'store']);


      
    });
});


Route::prefix('clientes')->group(function () {
   
    // Crear huésped (independiente del cliente)
    Route::post('huespedes', [HuespedController::class, 'store']);

    // (Opcional) Perfil completo 6 secciones en un solo POST
    Route::post('perfil-completo', [ClienteIntakeController::class, 'submit'])
        ->middleware('auth:sanctum'); // recomendado

   
});
