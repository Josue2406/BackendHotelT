<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\catalago_pago;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\reserva\ReservaPago;
/**
 * Class MetodoPago
 *
 * @property int $id_metodo_pago
 * @property string $codigo
 * @property int $id_moneda
 * @property string $nombre
 * @property string|null $descripcion
 * @property bool $activo
 * @property bool $requiere_autorizacion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|ReservaPago[] $reserva_pagos_where_id_metodo_pago
 * @property Collection|TransaccionPago[] $transaccion_pagos_where_id_metodo_pago
 *
 * @package App\Models
 */
class MetodoPago extends Model
{
	protected $table = 'metodo_pago';
	protected $primaryKey = 'id_metodo_pago';

	// Códigos de métodos de pago del Hotel Lanaku
	public const CREDITO = 'CR';              // Crédito (VIP/Agencias)
	public const EFECTIVO = 'CA';             // Cash (Efectivo)
	public const VISA_MASTERCARD = 'VI';      // Visa/Mastercard
	public const AMERICAN_EXPRESS = 'AX';     // American Express
	public const TRANSFERENCIA_BANCARIA = 'TB'; // Transferencia Bancaria

	protected $casts = [
		'id_moneda' => 'int',
		'activo' => 'boolean',
		'requiere_autorizacion' => 'boolean'
	];

	protected $fillable = [
		'codigo',
		'id_moneda',
		'nombre',
		'descripcion',
		'activo',
		'requiere_autorizacion'
	];

	public function id_moneda()
	{
		return $this->belongsTo(Moneda::class, 'id_moneda');
	}

	public function reserva_pagos_where_id_metodo_pago()
	{
		return $this->hasMany(ReservaPago::class, 'id_metodo_pago');
	}

	public function transaccion_pagos_where_id_metodo_pago()
	{
		return $this->hasMany(TransaccionPago::class, 'id_metodo_pago');
	}

	/**
	 * Obtener método de pago por código
	 *
	 * @param string $codigo
	 * @return MetodoPago|null
	 */
	public static function porCodigo(string $codigo): ?MetodoPago
	{
		return self::where('codigo', strtoupper($codigo))
			->where('activo', true)
			->first();
	}

	/**
	 * Verificar si el método de pago requiere autorización
	 *
	 * @return bool
	 */
	public function necesitaAutorizacion(): bool
	{
		return $this->requiere_autorizacion;
	}

	/**
	 * Verificar si el método de pago está activo
	 *
	 * @return bool
	 */
	public function estaActivo(): bool
	{
		return $this->activo;
	}

	/**
	 * Obtener todos los códigos disponibles
	 *
	 * @return array
	 */
	public static function obtenerCodigosDisponibles(): array
	{
		return [
			self::CREDITO => 'Crédito (Clientes VIP/Agencias)',
			self::EFECTIVO => 'Efectivo (Cash)',
			self::VISA_MASTERCARD => 'Visa/Mastercard',
			self::AMERICAN_EXPRESS => 'American Express',
			self::TRANSFERENCIA_BANCARIA => 'Transferencia Bancaria',
		];
	}

	/**
	 * Obtener métodos de pago activos
	 *
	 * @return Collection
	 */
	public static function activos(): Collection
	{
		return self::where('activo', true)->get();
	}

	/**
	 * Verificar si es un método de pago con crédito (diferido)
	 *
	 * @return bool
	 */
	public function esPagoDiferido(): bool
	{
		return $this->codigo === self::CREDITO;
	}

	/**
	 * Scope para filtrar por código
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param string $codigo
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeCodigo($query, string $codigo)
	{
		return $query->where('codigo', strtoupper($codigo));
	}

	/**
	 * Scope para métodos activos
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeActivo($query)
	{
		return $query->where('activo', true);
	}
}
