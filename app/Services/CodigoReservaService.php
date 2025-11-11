<?php

namespace App\Services;

use App\Models\reserva\Reserva;
use Illuminate\Support\Facades\DB;

class CodigoReservaService
{
    /**
     * Caracteres permitidos para el código (alfanuméricos sin caracteres confusos)
     * Excluimos: 0, O, I, 1, l para evitar confusiones
     */
    private const CARACTERES = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     * Longitud inicial del código
     */
    private const LONGITUD_INICIAL = 8;

    /**
     * Incremento de longitud cuando se alcanza el máximo
     */
    private const INCREMENTO_LONGITUD = 2;

    /**
     * Cache para la longitud actual
     */
    private static ?int $longitudActual = null;

    /**
     * Generar un código único para una nueva reserva
     *
     * @param int $maxIntentos Número máximo de intentos para generar código único
     * @return string
     * @throws \Exception
     */
    public function generarCodigoUnico(int $maxIntentos = 10): string
    {
        $longitud = $this->obtenerLongitudActual();

        for ($intento = 0; $intento < $maxIntentos; $intento++) {
            $codigo = $this->generarCodigoAleatorio($longitud);

            // Verificar si el código ya existe
            if (!$this->codigoExiste($codigo)) {
                return $codigo;
            }
        }

        // Si no se pudo generar después de varios intentos, aumentar longitud
        $nuevaLongitud = $longitud + self::INCREMENTO_LONGITUD;
        $this->actualizarLongitudActual($nuevaLongitud);

        // Intentar una vez más con la nueva longitud
        $codigo = $this->generarCodigoAleatorio($nuevaLongitud);

        if ($this->codigoExiste($codigo)) {
            throw new \Exception("No se pudo generar un código único después de {$maxIntentos} intentos");
        }

        return $codigo;
    }

    /**
     * Generar un código aleatorio de longitud específica
     *
     * @param int $longitud
     * @return string
     */
    private function generarCodigoAleatorio(int $longitud): string
    {
        $codigo = '';
        $caracteresLength = strlen(self::CARACTERES);

        for ($i = 0; $i < $longitud; $i++) {
            $codigo .= self::CARACTERES[random_int(0, $caracteresLength - 1)];
        }

        return $codigo;
    }

    /**
     * Verificar si un código ya existe en la base de datos
     *
     * @param string $codigo
     * @return bool
     */
    private function codigoExiste(string $codigo): bool
    {
        return Reserva::where('codigo_reserva', $codigo)->exists();
    }

    /**
     * Obtener la longitud actual que se debe usar para los códigos
     *
     * @return int
     */
    private function obtenerLongitudActual(): int
    {
        // Si ya está en cache, devolverlo
        if (self::$longitudActual !== null) {
            return self::$longitudActual;
        }

        // Calcular basado en el uso actual
        $longitudCalculada = $this->calcularLongitudNecesaria();

        self::$longitudActual = $longitudCalculada;

        return $longitudCalculada;
    }

    /**
     * Calcular la longitud necesaria basado en el número de reservas existentes
     *
     * @return int
     */
    private function calcularLongitudNecesaria(): int
    {
        // Contar solo reservas con código (las nuevas)
        $totalCodigosGenerados = Reserva::whereNotNull('codigo_reserva')->count();

        // Calcular el máximo de combinaciones posibles con cada longitud
        // Usamos 80% del máximo como umbral para cambiar
        $base = strlen(self::CARACTERES);
        $longitud = self::LONGITUD_INICIAL;

        while (true) {
            $maxCombinaciones = pow($base, $longitud);
            $umbral = $maxCombinaciones * 0.8; // 80% del máximo

            if ($totalCodigosGenerados < $umbral) {
                return $longitud;
            }

            $longitud += self::INCREMENTO_LONGITUD;

            // Límite de seguridad para evitar longitudes excesivas
            if ($longitud > 20) {
                return 20;
            }
        }
    }

    /**
     * Actualizar la longitud actual
     *
     * @param int $nuevaLongitud
     * @return void
     */
    private function actualizarLongitudActual(int $nuevaLongitud): void
    {
        self::$longitudActual = $nuevaLongitud;
    }

    /**
     * Obtener estadísticas sobre los códigos generados
     *
     * @return array
     */
    public function obtenerEstadisticas(): array
    {
        $longitudActual = $this->obtenerLongitudActual();
        $base = strlen(self::CARACTERES);
        $maxCombinaciones = pow($base, $longitudActual);
        $totalCodigosGenerados = Reserva::whereNotNull('codigo_reserva')->count();
        $porcentajeUso = $maxCombinaciones > 0 ? ($totalCodigosGenerados / $maxCombinaciones) * 100 : 0;

        return [
            'longitud_actual' => $longitudActual,
            'caracteres_disponibles' => self::CARACTERES,
            'total_caracteres' => $base,
            'max_combinaciones_posibles' => number_format($maxCombinaciones, 0),
            'codigos_generados' => $totalCodigosGenerados,
            'porcentaje_uso' => round($porcentajeUso, 4),
            'proxima_longitud_en' => $longitudActual + self::INCREMENTO_LONGITUD,
            'umbral_cambio' => round($maxCombinaciones * 0.8),
            'codigos_disponibles' => number_format($maxCombinaciones - $totalCodigosGenerados, 0),
        ];
    }

    /**
     * Validar formato de código de reserva
     *
     * @param string $codigo
     * @return bool
     */
    public function validarFormatoCodigo(string $codigo): bool
    {
        $longitud = strlen($codigo);

        // Debe tener al menos la longitud mínima
        if ($longitud < self::LONGITUD_INICIAL) {
            return false;
        }

        // Todos los caracteres deben estar en el conjunto permitido
        $caracteresPermitidos = str_split(self::CARACTERES);
        $caracteresCode = str_split($codigo);

        foreach ($caracteresCode as $char) {
            if (!in_array($char, $caracteresPermitidos)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Formatear código para visualización (agrega guiones para legibilidad)
     * Ejemplo: ABC123XY → ABC1-23XY
     *
     * @param string $codigo
     * @return string
     */
    public function formatearCodigo(string $codigo): string
    {
        $longitud = strlen($codigo);

        if ($longitud <= 4) {
            return $codigo;
        }

        // Dividir en grupos de 4 caracteres
        $grupos = str_split($codigo, 4);

        return implode('-', $grupos);
    }

    /**
     * Buscar reserva por código
     *
     * @param string $codigo
     * @return Reserva|null
     */
    public function buscarPorCodigo(string $codigo): ?Reserva
    {
        // Remover guiones si los tiene
        $codigoLimpio = str_replace('-', '', strtoupper($codigo));

        return Reserva::where('codigo_reserva', $codigoLimpio)->first();
    }
}