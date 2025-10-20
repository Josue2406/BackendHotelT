<?php

namespace App\Http\Controllers\Api\reserva;

use App\Http\Controllers\Controller;
use App\Models\reserva\Reserva;
use App\Models\reserva\ReservaHabitacion;
use App\Models\habitacion\Habitacione;
use App\Models\habitacion\TiposHabitacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    /**
     * Helper: Calcular rango de fechas según período
     */
    private function calcularRangoFechas(Request $request)
    {
        // Si se envían fechas específicas, usarlas
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            return [
                'desde' => Carbon::parse($request->input('fecha_desde'))->startOfDay(),
                'hasta' => Carbon::parse($request->input('fecha_hasta'))->endOfDay(),
            ];
        }

        // Si se envía período predefinido
        $periodo = $request->input('periodo', '30d');
        $hasta = Carbon::now()->endOfDay();

        $desde = match($periodo) {
            '7d' => Carbon::now()->subDays(7)->startOfDay(),
            '30d' => Carbon::now()->subDays(30)->startOfDay(),
            '3m' => Carbon::now()->subMonths(3)->startOfDay(),
            '6m' => Carbon::now()->subMonths(6)->startOfDay(),
            '1y' => Carbon::now()->subYear()->startOfDay(),
            'all' => Carbon::parse('2020-01-01')->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        return compact('desde', 'hasta');
    }

    /**
     * Helper: Aplicar filtros a la query
     */
    private function aplicarFiltros($query, Request $request, $fechaDesde, $fechaHasta)
    {
        // Filtrar por rango de fechas (fecha_entrada de las habitaciones)
        $query->whereHas('habitaciones', function($q) use ($fechaDesde, $fechaHasta) {
            $q->whereBetween('fecha_llegada', [$fechaDesde, $fechaHasta]);
        });

        // Filtrar por tipo de habitación
        if ($request->filled('tipo_habitacion')) {
            $query->whereHas('habitaciones.habitacion.tipoHabitacion', function($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->input('tipo_habitacion') . '%');
            });
        }

        // Filtrar por estado
        if ($request->filled('estado')) {
            $estadoMap = [
                'confirmed' => 'Confirmada',
                'cancelled' => 'Cancelada',
                'pending' => 'Pendiente',
                'checkin' => 'Check-in',
                'noshow' => 'No-show'
            ];

            $estadoBuscado = $estadoMap[$request->input('estado')] ?? $request->input('estado');

            $query->whereHas('estado', function($q) use ($estadoBuscado) {
                $q->where('nombre', $estadoBuscado);
            });
        }

        // Filtrar por fuente
        if ($request->filled('fuente')) {
            $query->whereHas('fuente', function($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->input('fuente') . '%');
            });
        }

        return $query;
    }

    /**
     * GET /api/reservas/reportes/kpis
     * Retorna métricas clave (KPIs)
     */
    public function kpis(Request $request)
    {
        try {
            // Validar parámetros
            $request->validate([
                'periodo' => 'nullable|in:7d,30d,3m,6m,1y,all',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'tipo_habitacion' => 'nullable|string',
                'estado' => 'nullable|string',
                'fuente' => 'nullable|string',
            ]);

            $rango = $this->calcularRangoFechas($request);
            $fechaDesde = $rango['desde'];
            $fechaHasta = $rango['hasta'];

            // Query base con filtros
            $queryReservas = Reserva::query();
            $queryReservas = $this->aplicarFiltros($queryReservas, $request, $fechaDesde, $fechaHasta);

            // 1. Total de reservas
            $totalReservations = $queryReservas->count();

            // 2. Reservas confirmadas
            $confirmedReservations = (clone $queryReservas)
                ->whereHas('estado', function($q) {
                    $q->where('nombre', 'Confirmada');
                })
                ->count();

            // 3. Reservas canceladas
            $cancelledReservations = (clone $queryReservas)
                ->whereHas('estado', function($q) {
                    $q->where('nombre', 'Cancelada');
                })
                ->count();

            // 4. Ingresos totales (solo de reservas confirmadas y check-in)
            $totalRevenue = (clone $queryReservas)
                ->whereHas('estado', function($q) {
                    $q->whereIn('nombre', ['Confirmada', 'Check-in']);
                })
                ->sum('total_monto_reserva');

            // 5. Total de noches reservadas (para calcular ADR)
            $reservasConfirmadas = DB::table('reserva as r')
                ->join('estado_reserva as er', 'r.id_estado_res', '=', 'er.id_estado_res')
                ->join('reserva_habitacions as rh', 'r.id_reserva', '=', 'rh.id_reserva')
                ->whereIn('er.nombre', ['Confirmada', 'Check-in'])
                ->whereBetween('rh.fecha_llegada', [$fechaDesde, $fechaHasta])
                ->select('rh.id_reserva_hab', 'rh.fecha_llegada', 'rh.fecha_salida')
                ->get();

            $totalNochesReservadas = $reservasConfirmadas->sum(function($rh) {
                return Carbon::parse($rh->fecha_llegada)->diffInDays(Carbon::parse($rh->fecha_salida));
            });

            // 6. Average Daily Rate (ADR)
            $averageDailyRate = $totalNochesReservadas > 0
                ? round($totalRevenue / $totalNochesReservadas, 2)
                : 0;

            // 7. Ocupación (habitaciones únicas ocupadas en el período)
            $totalHabitaciones = Habitacione::count();
            $diasPeriodo = $fechaDesde->diffInDays($fechaHasta);

            // Contar habitaciones ocupadas por día
            $habitacionesOcupadasPorDia = ReservaHabitacion::whereBetween('fecha_llegada', [$fechaDesde, $fechaHasta])
                ->whereHas('reserva.estado', function($q) {
                    $q->whereIn('nombre', ['Confirmada', 'Check-in']);
                })
                ->get()
                ->sum(function($rh) {
                    return Carbon::parse($rh->fecha_llegada)->diffInDays(Carbon::parse($rh->fecha_salida));
                });

            $occupancyRate = ($totalHabitaciones > 0 && $diasPeriodo > 0)
                ? round(($habitacionesOcupadasPorDia / ($totalHabitaciones * $diasPeriodo)) * 100, 2)
                : 0;

            // 8. RevPAR (Revenue Per Available Room)
            $revPAR = ($totalHabitaciones > 0 && $diasPeriodo > 0)
                ? round($totalRevenue / ($totalHabitaciones * $diasPeriodo), 2)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'occupancyRate' => $occupancyRate,
                    'totalRevenue' => round($totalRevenue, 2),
                    'confirmedReservations' => $confirmedReservations,
                    'cancelledReservations' => $cancelledReservations,
                    'totalReservations' => $totalReservations,
                    'averageDailyRate' => $averageDailyRate,
                    'revPAR' => $revPAR,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors' => $e->errors()
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error al generar KPIs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reservas/reportes/series-temporales
     * Datos agregados por fecha para gráficos de línea/barras
     */
    public function seriesTemporales(Request $request)
    {
        try {
            // Validar parámetros
            $request->validate([
                'periodo' => 'nullable|in:7d,30d,3m,6m,1y,all',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'tipo_habitacion' => 'nullable|string',
                'estado' => 'nullable|string',
                'fuente' => 'nullable|string',
            ]);

            $rango = $this->calcularRangoFechas($request);
            $fechaDesde = $rango['desde'];
            $fechaHasta = $rango['hasta'];

            $totalHabitaciones = Habitacione::count();

            // Query con agregación por fecha
            $series = DB::table('reserva_habitacions as rh')
                ->join('reserva as r', 'rh.id_reserva', '=', 'r.id_reserva')
                ->join('estado_reserva as er', 'r.id_estado_res', '=', 'er.id_estado_res')
                ->whereBetween('rh.fecha_llegada', [$fechaDesde, $fechaHasta])
                ->select(
                    DB::raw('DATE(rh.fecha_llegada) as date'),
                    DB::raw('COUNT(DISTINCT r.id_reserva) as reservations'),
                    DB::raw('SUM(CASE WHEN er.nombre IN ("Confirmada", "Check-in") THEN r.total_monto_reserva ELSE 0 END) as revenue'),
                    DB::raw('SUM(CASE WHEN er.nombre = "Cancelada" THEN 1 ELSE 0 END) as cancellations'),
                    DB::raw('COUNT(DISTINCT CASE WHEN er.nombre IN ("Confirmada", "Check-in") THEN rh.id_habitacion END) as rooms_occupied')
                )
                ->groupBy(DB::raw('DATE(rh.fecha_llegada)'))
                ->orderBy('date', 'asc')
                ->get()
                ->map(function($item) use ($totalHabitaciones) {
                    return [
                        'date' => $item->date,
                        'reservations' => (int) $item->reservations,
                        'revenue' => round((float) $item->revenue, 2),
                        'occupancy' => $totalHabitaciones > 0
                            ? round(((float) $item->rooms_occupied / $totalHabitaciones) * 100, 2)
                            : 0,
                        'cancellations' => (int) $item->cancellations,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $series
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors' => $e->errors()
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error al generar series temporales', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reservas/reportes/distribuciones
     * Datos agregados por categorías para gráficos pie/donut
     */
    public function distribuciones(Request $request)
    {
        try {
            // Validar parámetros
            $request->validate([
                'periodo' => 'nullable|in:7d,30d,3m,6m,1y,all',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'tipo_habitacion' => 'nullable|string',
                'estado' => 'nullable|string',
                'fuente' => 'nullable|string',
            ]);

            $rango = $this->calcularRangoFechas($request);
            $fechaDesde = $rango['desde'];
            $fechaHasta = $rango['hasta'];

            // Query base con filtros
            $queryReservas = Reserva::query();
            $queryReservas = $this->aplicarFiltros($queryReservas, $request, $fechaDesde, $fechaHasta);

            $totalReservas = $queryReservas->count();

            // 1. Distribución por tipo de habitación
            $byRoomType = DB::table('reserva_habitacions as rh')
                ->join('reserva as r', 'rh.id_reserva', '=', 'r.id_reserva')
                ->join('habitaciones as h', 'rh.id_habitacion', '=', 'h.id_habitacion')
                ->join('tipos_habitacion as th', 'h.tipo_habitacion_id', '=', 'th.id_tipo_hab')
                ->whereBetween('rh.fecha_llegada', [$fechaDesde, $fechaHasta])
                ->select(
                    'th.nombre as name',
                    DB::raw('COUNT(DISTINCT r.id_reserva) as value')
                )
                ->groupBy('th.nombre')
                ->get()
                ->map(function($item) use ($totalReservas) {
                    return [
                        'name' => $item->name,
                        'value' => (int) $item->value,
                        'percentage' => $totalReservas > 0
                            ? round(((int) $item->value / $totalReservas) * 100, 2)
                            : 0
                    ];
                })
                ->values();

            // 2. Distribución por fuente
            $bySource = DB::table('reserva as r')
                ->join('fuentes as f', 'r.id_fuente', '=', 'f.id_fuente')
                ->join('reserva_habitacions as rh', 'r.id_reserva', '=', 'rh.id_reserva')
                ->whereBetween('rh.fecha_llegada', [$fechaDesde, $fechaHasta])
                ->select(
                    'f.nombre as name',
                    DB::raw('COUNT(DISTINCT r.id_reserva) as value')
                )
                ->groupBy('f.nombre')
                ->get()
                ->map(function($item) use ($totalReservas) {
                    return [
                        'name' => $item->name,
                        'value' => (int) $item->value,
                        'percentage' => $totalReservas > 0
                            ? round(((int) $item->value / $totalReservas) * 100, 2)
                            : 0
                    ];
                })
                ->values();

            // 3. Distribución por estado
            $byStatus = DB::table('reserva as r')
                ->join('estado_reserva as er', 'r.id_estado_res', '=', 'er.id_estado_res')
                ->join('reserva_habitacions as rh', 'r.id_reserva', '=', 'rh.id_reserva')
                ->whereBetween('rh.fecha_llegada', [$fechaDesde, $fechaHasta])
                ->select(
                    'er.nombre as name',
                    DB::raw('COUNT(DISTINCT r.id_reserva) as value')
                )
                ->groupBy('er.nombre')
                ->get()
                ->map(function($item) use ($totalReservas) {
                    return [
                        'name' => $item->name,
                        'value' => (int) $item->value,
                        'percentage' => $totalReservas > 0
                            ? round(((int) $item->value / $totalReservas) * 100, 2)
                            : 0
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'byRoomType' => $byRoomType,
                    'bySource' => $bySource,
                    'byStatus' => $byStatus,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors' => $e->errors()
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error al generar distribuciones', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reservas/reportes/export/pdf
     * Generar y descargar reporte completo en PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            // Validar parámetros
            $request->validate([
                'periodo' => 'nullable|in:7d,30d,3m,6m,1y,all',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'incluir_graficos' => 'nullable|boolean',
                'incluir_tabla' => 'nullable|boolean',
                'idioma' => 'nullable|in:es,en',
            ]);

            $rango = $this->calcularRangoFechas($request);
            $fechaDesde = $rango['desde'];
            $fechaHasta = $rango['hasta'];

            $incluirGraficos = $request->input('incluir_graficos', true);
            $incluirTabla = $request->input('incluir_tabla', true);

            // 1. Obtener KPIs
            $kpisRequest = new Request($request->all());
            $kpisResponse = $this->kpis($kpisRequest);
            $kpisData = json_decode($kpisResponse->getContent());

            // Verificar si la respuesta de KPIs fue exitosa
            if (!isset($kpisData->data)) {
                throw new \Exception('Error al obtener KPIs: ' . ($kpisData->message ?? 'Respuesta inválida'));
            }
            $kpis = $kpisData->data;

            // 2. Obtener distribuciones (si se solicitan gráficos)
            $distribuciones = null;
            if ($incluirGraficos) {
                $distribResponse = $this->distribuciones($kpisRequest);
                $distribData = json_decode($distribResponse->getContent());

                // Verificar si la respuesta de distribuciones fue exitosa
                if (!isset($distribData->data)) {
                    throw new \Exception('Error al obtener distribuciones: ' . ($distribData->message ?? 'Respuesta inválida'));
                }
                $distribuciones = $distribData->data;
            }

            // 3. Obtener reservas (si se solicita tabla)
            $reservas = null;
            if ($incluirTabla) {
                $queryReservas = Reserva::with(['cliente', 'estado', 'habitaciones'])
                    ->whereHas('habitaciones', function($q) use ($fechaDesde, $fechaHasta) {
                        $q->whereBetween('fecha_llegada', [$fechaDesde, $fechaHasta]);
                    })
                    ->limit(100) // Límite para no sobrecargar el PDF
                    ->get();

                $reservas = $queryReservas;
            }

            // 4. Preparar datos para la vista
            $periodo = $request->input('periodo', '30d');
            $periodoTexto = match($periodo) {
                '7d' => 'Últimos 7 días',
                '30d' => 'Últimos 30 días',
                '3m' => 'Últimos 3 meses',
                '6m' => 'Últimos 6 meses',
                '1y' => 'Último año',
                'all' => 'Todo el historial',
                default => 'Período personalizado',
            };

            $data = [
                'kpis' => json_decode(json_encode($kpis), true),
                'distribuciones' => $distribuciones ? json_decode(json_encode($distribuciones), true) : null,
                'reservas' => $reservas,
                'incluir_graficos' => $incluirGraficos,
                'incluir_tabla' => $incluirTabla,
                'periodo_texto' => $periodoTexto,
                'fecha_desde' => $fechaDesde->format('Y-m-d'),
                'fecha_hasta' => $fechaHasta->format('Y-m-d'),
                'fecha_generacion' => Carbon::now()->format('d/m/Y H:i:s'),
            ];

            // 5. Generar PDF
            $pdf = Pdf::loadView('reportes.pdf-reservas', $data);
            $pdf->setPaper('letter', 'portrait');

            // 6. Nombre del archivo
            $filename = 'reporte-reservas-' . $fechaDesde->format('Y-m-d') . '.pdf';

            // 7. Retornar PDF para descarga
            return $pdf->download($filename);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors' => $e->errors()
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error al exportar PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el PDF.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
