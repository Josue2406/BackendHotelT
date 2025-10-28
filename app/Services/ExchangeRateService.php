<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    // API gratuita de tipos de cambio (actualizada diariamente)
    // https://www.exchangerate-api.com/
    private const API_URL = 'https://api.exchangerate-api.com/v4/latest/USD';

    // Cache por 12 horas (la API se actualiza diariamente)
    private const CACHE_TTL = 43200; // 12 horas en segundos

    // Monedas soportadas
    public const MONEDAS_SOPORTADAS = [
        'USD' => 'Dólar Estadounidense',
        'CRC' => 'Colón Costarricense',
        'EUR' => 'Euro',
        'GBP' => 'Libra Esterlina',
        'CAD' => 'Dólar Canadiense',
        'MXN' => 'Peso Mexicano',
        'JPY' => 'Yen Japonés',
        'CNY' => 'Yuan Chino',
        'BRL' => 'Real Brasileño',
        'ARS' => 'Peso Argentino',
        'COP' => 'Peso Colombiano',
        'CLP' => 'Peso Chileno',
        'PEN' => 'Sol Peruano',
        'CHF' => 'Franco Suizo',
        'AUD' => 'Dólar Australiano',
        'NZD' => 'Dólar Neozelandés',
    ];

    /**
     * Obtener tipos de cambio actuales (con cache)
     *
     * @return array
     */
    public function obtenerTiposDeCambio(): array
    {
        try {
            return Cache::remember('exchange_rates', self::CACHE_TTL, function () {
                $response = Http::timeout(10)->get(self::API_URL);

                if ($response->successful()) {
                    $data = $response->json();

                    Log::info('Tipos de cambio actualizados desde API', [
                        'base' => $data['base'] ?? 'USD',
                        'date' => $data['date'] ?? now()->toDateString(),
                    ]);

                    return $data['rates'] ?? [];
                }

                Log::error('Error al obtener tipos de cambio desde API', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return $this->obtenerTiposDeCambioFallback();
            });

        } catch (\Exception $e) {
            Log::error('Excepción al obtener tipos de cambio', [
                'error' => $e->getMessage()
            ]);

            return $this->obtenerTiposDeCambioFallback();
        }
    }

    /**
     * Obtener tipo de cambio para una moneda específica
     *
     * @param string $codigoMoneda Código de moneda (ej: CRC, EUR)
     * @return float
     */
    public function obtenerTipoCambio(string $codigoMoneda): float
    {
        // Si es USD, retornar 1
        if (strtoupper($codigoMoneda) === 'USD') {
            return 1.0;
        }

        $tasas = $this->obtenerTiposDeCambio();
        $codigo = strtoupper($codigoMoneda);

        if (isset($tasas[$codigo])) {
            return (float) $tasas[$codigo];
        }

        Log::warning('Tipo de cambio no encontrado para moneda', [
            'moneda' => $codigo,
            'usando_default' => 1.0
        ]);

        return 1.0; // Fallback
    }

    /**
     * Convertir monto de USD a otra moneda
     *
     * @param float $montoUSD Monto en dólares
     * @param string $monedaDestino Código de moneda destino
     * @return array ['monto' => float, 'tipo_cambio' => float]
     */
    public function convertirDesdeUSD(float $montoUSD, string $monedaDestino): array
    {
        $tipoCambio = $this->obtenerTipoCambio($monedaDestino);
        $montoConvertido = $montoUSD * $tipoCambio;

        return [
            'monto' => round($montoConvertido, 2),
            'tipo_cambio' => $tipoCambio,
            'monto_usd' => $montoUSD,
            'moneda' => strtoupper($monedaDestino),
        ];
    }

    /**
     * Convertir monto de cualquier moneda a USD
     *
     * @param float $monto Monto en la moneda origen
     * @param string $monedaOrigen Código de moneda origen
     * @return float Monto en USD
     */
    public function convertirAUSD(float $monto, string $monedaOrigen): float
    {
        // Si ya es USD, retornar el mismo monto
        if (strtoupper($monedaOrigen) === 'USD') {
            return $monto;
        }

        $tipoCambio = $this->obtenerTipoCambio($monedaOrigen);

        if ($tipoCambio == 0) {
            return $monto; // Evitar división por cero
        }

        return round($monto / $tipoCambio, 2);
    }

    /**
     * Convertir entre dos monedas (no USD)
     *
     * @param float $monto
     * @param string $monedaOrigen
     * @param string $monedaDestino
     * @return array
     */
    public function convertir(float $monto, string $monedaOrigen, string $monedaDestino): array
    {
        // Primero convertir a USD
        $montoUSD = $this->convertirAUSD($monto, $monedaOrigen);

        // Luego convertir a moneda destino
        return $this->convertirDesdeUSD($montoUSD, $monedaDestino);
    }

    /**
     * Obtener información completa de conversión
     *
     * @param float $montoUSD
     * @param string $monedaDestino
     * @return array
     */
    public function obtenerInfoConversion(float $montoUSD, string $monedaDestino): array
    {
        $conversion = $this->convertirDesdeUSD($montoUSD, $monedaDestino);
        $tasas = $this->obtenerTiposDeCambio();

        return [
            'monto_original_usd' => $montoUSD,
            'monto_convertido' => $conversion['monto'],
            'moneda_destino' => strtoupper($monedaDestino),
            'tipo_cambio' => $conversion['tipo_cambio'],
            'formula' => "1 USD = {$conversion['tipo_cambio']} {$monedaDestino}",
            'fecha_actualizacion' => Cache::get('exchange_rates_date', now()->toDateString()),
            'todas_las_tasas' => $this->filtrarMonedasSoportadas($tasas),
        ];
    }

    /**
     * Limpiar cache de tipos de cambio (forzar actualización)
     *
     * @return bool
     */
    public function limpiarCache(): bool
    {
        Cache::forget('exchange_rates');
        Cache::forget('exchange_rates_date');

        Log::info('Cache de tipos de cambio limpiado manualmente');

        return true;
    }

    /**
     * Verificar si una moneda está soportada
     *
     * @param string $codigoMoneda
     * @return bool
     */
    public function estaMonedaSoportada(string $codigoMoneda): bool
    {
        return isset(self::MONEDAS_SOPORTADAS[strtoupper($codigoMoneda)]);
    }

    /**
     * Obtener lista de monedas soportadas con sus tasas actuales
     *
     * @return array
     */
    public function obtenerMonedasSoportadas(): array
    {
        $tasas = $this->obtenerTiposDeCambio();
        $resultado = [];

        foreach (self::MONEDAS_SOPORTADAS as $codigo => $nombre) {
            $resultado[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tasa' => $tasas[$codigo] ?? 1.0,
                'simbolo' => $this->obtenerSimbolo($codigo),
            ];
        }

        return $resultado;
    }

    /**
     * Obtener símbolo de moneda
     *
     * @param string $codigo
     * @return string
     */
    private function obtenerSimbolo(string $codigo): string
    {
        $simbolos = [
            'USD' => '$',
            'CRC' => '₡',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'CA$',
            'MXN' => 'MX$',
            'JPY' => '¥',
            'CNY' => '¥',
            'BRL' => 'R$',
            'ARS' => 'AR$',
            'COP' => 'CO$',
            'CLP' => 'CL$',
            'PEN' => 'S/',
            'CHF' => 'CHF',
            'AUD' => 'A$',
            'NZD' => 'NZ$',
        ];

        return $simbolos[strtoupper($codigo)] ?? $codigo;
    }

    /**
     * Filtrar solo las monedas que soportamos
     *
     * @param array $todasLasTasas
     * @return array
     */
    private function filtrarMonedasSoportadas(array $todasLasTasas): array
    {
        $filtradas = [];

        foreach (self::MONEDAS_SOPORTADAS as $codigo => $nombre) {
            if (isset($todasLasTasas[$codigo])) {
                $filtradas[$codigo] = $todasLasTasas[$codigo];
            }
        }

        return $filtradas;
    }

    /**
     * Obtener detalles completos de conversión para mostrar al cliente
     * Usado principalmente en el flujo de pagos
     *
     * @param float $monto Monto en la moneda seleccionada
     * @param string $monedaSeleccionada Moneda en la que el cliente pagará
     * @return array
     */
    public function obtenerDetallesPago(float $monto, string $monedaSeleccionada): array
    {
        $monedaSeleccionada = strtoupper($monedaSeleccionada);
        $tipoCambio = $this->obtenerTipoCambio($monedaSeleccionada);
        $montoUSD = $this->convertirAUSD($monto, $monedaSeleccionada);

        return [
            'monto_pagado' => round($monto, 2),
            'moneda_pago' => $monedaSeleccionada,
            'moneda_nombre' => self::MONEDAS_SOPORTADAS[$monedaSeleccionada] ?? $monedaSeleccionada,
            'simbolo_moneda' => $this->obtenerSimbolo($monedaSeleccionada),
            'tipo_cambio' => $tipoCambio,
            'monto_usd' => round($montoUSD, 2),
            'equivalente_texto' => "{$this->obtenerSimbolo($monedaSeleccionada)}" . number_format($monto, 2) . " = $" . number_format($montoUSD, 2) . " USD",
            'fecha_tipo_cambio' => now()->toDateString(),
        ];
    }

    /**
     * Calcular monto en múltiples divisas principales (USD, CRC, EUR)
     * Usado para mostrar precios en las 3 divisas principales del hotel
     *
     * @param float $montoUSD Precio base en USD
     * @return array
     */
    public function calcularPrecioMultidivisa(float $montoUSD): array
    {
        $crc = $this->convertirDesdeUSD($montoUSD, 'CRC');
        $eur = $this->convertirDesdeUSD($montoUSD, 'EUR');

        return [
            'usd' => [
                'monto' => round($montoUSD, 2),
                'simbolo' => '$',
                'codigo' => 'USD',
                'nombre' => 'Dólar Estadounidense',
                'formato' => '$' . number_format($montoUSD, 2),
            ],
            'crc' => [
                'monto' => $crc['monto'],
                'simbolo' => '₡',
                'codigo' => 'CRC',
                'nombre' => 'Colón Costarricense',
                'tipo_cambio' => $crc['tipo_cambio'],
                'formato' => '₡' . number_format($crc['monto'], 2),
            ],
            'eur' => [
                'monto' => $eur['monto'],
                'simbolo' => '€',
                'codigo' => 'EUR',
                'nombre' => 'Euro',
                'tipo_cambio' => $eur['tipo_cambio'],
                'formato' => '€' . number_format($eur['monto'], 2),
            ],
        ];
    }

    /**
     * Obtener las 3 divisas principales del Hotel Lanaku
     *
     * @return array
     */
    public function obtenerDivisasPrincipales(): array
    {
        return ['USD', 'CRC', 'EUR'];
    }

    /**
     * Validar que la moneda sea una de las principales del hotel
     *
     * @param string $codigoMoneda
     * @return bool
     */
    public function esDivisaPrincipal(string $codigoMoneda): bool
    {
        return in_array(strtoupper($codigoMoneda), $this->obtenerDivisasPrincipales());
    }

    /**
     * Tipos de cambio de fallback (valores aproximados)
     * Se usan si la API falla
     *
     * @return array
     */
    private function obtenerTiposDeCambioFallback(): array
    {
        Log::warning('Usando tipos de cambio de fallback (valores fijos)');

        return [
            'USD' => 1.0,
            'CRC' => 520.00,  // Colón Costarricense
            'EUR' => 0.92,    // Euro
            'GBP' => 0.79,    // Libra Esterlina
            'CAD' => 1.36,    // Dólar Canadiense
            'MXN' => 17.50,   // Peso Mexicano
            'JPY' => 149.50,  // Yen Japonés
            'CNY' => 7.24,    // Yuan Chino
            'BRL' => 4.98,    // Real Brasileño
            'ARS' => 350.00,  // Peso Argentino
            'COP' => 4100.00, // Peso Colombiano
            'CLP' => 920.00,  // Peso Chileno
            'PEN' => 3.75,    // Sol Peruano
            'CHF' => 0.88,    // Franco Suizo
            'AUD' => 1.53,    // Dólar Australiano
            'NZD' => 1.67,    // Dólar Neozelandés
        ];
    }
}