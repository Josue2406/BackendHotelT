<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Reservas - {{ $fecha_generacion }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .container {
            width: 100%;
            padding: 20px;
        }

        /* Portada */
        .portada {
            text-align: center;
            padding: 40px 0;
            border-bottom: 3px solid #2c3e50;
            margin-bottom: 30px;
        }

        .portada h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .portada .periodo {
            font-size: 14px;
            color: #7f8c8d;
            margin: 10px 0;
        }

        .portada .fecha-generacion {
            font-size: 10px;
            color: #95a5a6;
            margin-top: 20px;
        }

        /* KPIs */
        .seccion {
            margin-bottom: 30px;
        }

        .seccion-titulo {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }

        .kpis-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .kpi-card {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }

        .kpi-label {
            font-size: 10px;
            color: #7f8c8d;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .kpi-valor {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }

        .kpi-valor.success {
            color: #27ae60;
        }

        .kpi-valor.danger {
            color: #e74c3c;
        }

        .kpi-valor.warning {
            color: #f39c12;
        }

        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table thead {
            background-color: #3498db;
            color: white;
        }

        table th {
            padding: 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }

        table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 9px;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table tbody tr:hover {
            background-color: #e8f4f8;
        }

        /* Distribuciones */
        .distribucion-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .distribucion-nombre {
            font-weight: bold;
            color: #2c3e50;
        }

        .distribucion-valor {
            color: #7f8c8d;
        }

        .distribucion-barra {
            width: 200px;
            height: 15px;
            background: #ecf0f1;
            border-radius: 3px;
            overflow: hidden;
            margin: 0 10px;
        }

        .distribucion-barra-fill {
            height: 100%;
            background: #3498db;
        }

        /* Gráfico simple de barras */
        .chart-container {
            margin: 20px 0;
        }

        .chart-bar {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .chart-label {
            width: 120px;
            font-size: 9px;
            color: #7f8c8d;
        }

        .chart-bar-container {
            flex: 1;
            height: 20px;
            background: #ecf0f1;
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }

        .chart-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2980b9);
            transition: width 0.3s;
        }

        .chart-value {
            margin-left: 10px;
            font-size: 9px;
            font-weight: bold;
            color: #2c3e50;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            text-align: center;
            font-size: 9px;
            color: #95a5a6;
        }

        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- PORTADA -->
        <div class="portada">
            <h1>📊 Reporte de Reservas</h1>
            <div class="periodo">
                <strong>Período:</strong> {{ $periodo_texto }}
            </div>
            @if(isset($fecha_desde) && isset($fecha_hasta))
            <div class="periodo">
                {{ \Carbon\Carbon::parse($fecha_desde)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fecha_hasta)->format('d/m/Y') }}
            </div>
            @endif
            <div class="fecha-generacion">
                Generado el {{ $fecha_generacion }}
            </div>
        </div>

        <!-- SECCIÓN KPIs -->
        <div class="seccion">
            <h2 class="seccion-titulo">Indicadores Clave de Rendimiento (KPIs)</h2>

            <div class="kpis-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Tasa de Ocupación</div>
                    <div class="kpi-valor {{ $kpis['occupancyRate'] >= 70 ? 'success' : ($kpis['occupancyRate'] >= 50 ? 'warning' : 'danger') }}">
                        {{ number_format($kpis['occupancyRate'], 2) }}%
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Ingresos Totales</div>
                    <div class="kpi-valor success">
                        ${{ number_format($kpis['totalRevenue'], 2) }}
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Total Reservas</div>
                    <div class="kpi-valor">
                        {{ number_format($kpis['totalReservations']) }}
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Reservas Confirmadas</div>
                    <div class="kpi-valor success">
                        {{ number_format($kpis['confirmedReservations']) }}
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Reservas Canceladas</div>
                    <div class="kpi-valor danger">
                        {{ number_format($kpis['cancelledReservations']) }}
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Tarifa Promedio (ADR)</div>
                    <div class="kpi-valor">
                        ${{ number_format($kpis['averageDailyRate'], 2) }}
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">RevPAR</div>
                    <div class="kpi-valor">
                        ${{ number_format($kpis['revPAR'], 2) }}
                    </div>
                </div>
            </div>
        </div>

        @if($incluir_graficos && isset($distribuciones))
        <!-- SECCIÓN DISTRIBUCIONES -->
        <div class="seccion page-break">
            <h2 class="seccion-titulo">Distribuciones</h2>

            <!-- Por Tipo de Habitación -->
            <h3 style="font-size: 13px; margin: 20px 0 10px 0; color: #2c3e50;">Por Tipo de Habitación</h3>
            <div class="chart-container">
                @foreach($distribuciones['byRoomType'] as $item)
                <div class="chart-bar">
                    <div class="chart-label">{{ $item['name'] }}</div>
                    <div class="chart-bar-container">
                        <div class="chart-bar-fill" style="width: {{ $item['percentage'] }}%"></div>
                    </div>
                    <div class="chart-value">{{ $item['value'] }} ({{ number_format($item['percentage'], 1) }}%)</div>
                </div>
                @endforeach
            </div>

            <!-- Por Fuente -->
            <h3 style="font-size: 13px; margin: 20px 0 10px 0; color: #2c3e50;">Por Fuente de Reserva</h3>
            <div class="chart-container">
                @foreach($distribuciones['bySource'] as $item)
                <div class="chart-bar">
                    <div class="chart-label">{{ $item['name'] }}</div>
                    <div class="chart-bar-container">
                        <div class="chart-bar-fill" style="width: {{ $item['percentage'] }}%"></div>
                    </div>
                    <div class="chart-value">{{ $item['value'] }} ({{ number_format($item['percentage'], 1) }}%)</div>
                </div>
                @endforeach
            </div>

            <!-- Por Estado -->
            <h3 style="font-size: 13px; margin: 20px 0 10px 0; color: #2c3e50;">Por Estado de Reserva</h3>
            <div class="chart-container">
                @foreach($distribuciones['byStatus'] as $item)
                <div class="chart-bar">
                    <div class="chart-label">{{ $item['name'] }}</div>
                    <div class="chart-bar-container">
                        <div class="chart-bar-fill" style="width: {{ $item['percentage'] }}%"></div>
                    </div>
                    <div class="chart-value">{{ $item['value'] }} ({{ number_format($item['percentage'], 1) }}%)</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($incluir_tabla && isset($reservas) && count($reservas) > 0)
        <!-- SECCIÓN TABLA DE RESERVAS -->
        <div class="seccion page-break">
            <h2 class="seccion-titulo">Detalle de Reservas</h2>

            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Estado</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservas as $reserva)
                    <tr>
                        <td>{{ $reserva->codigo_reserva ?? 'N/A' }}</td>
                        <td>{{ $reserva->cliente->nombre ?? 'N/A' }} {{ $reserva->cliente->apellido1 ?? '' }}</td>
                        <td>{{ $reserva->habitaciones->first()->fecha_llegada ?? 'N/A' }}</td>
                        <td>{{ $reserva->habitaciones->first()->fecha_salida ?? 'N/A' }}</td>
                        <td>{{ $reserva->estado->nombre ?? 'N/A' }}</td>
                        <td>${{ number_format($reserva->total_monto_reserva, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top: 15px; text-align: right; font-size: 10px; color: #7f8c8d;">
                Total de registros: {{ count($reservas) }}
            </div>
        </div>
        @endif

        <!-- FOOTER -->
        <div class="footer">
            <p>Sistema de Gestión Hotelera - Reporte generado automáticamente</p>
            <p>{{ $fecha_generacion }}</p>
        </div>

    </div>
</body>
</html>
