<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class NuevaEntradaFolio
 * 
 * @property int $id_nueva_entrada_folio
 * @property int $id_folio
 * @property int $id_tipo_entrada
 * @property int $id_tipo_concepto
 * @property string|null $descripcion
 * @property float $monto
 * @property Carbon $fecha
 * @property int $id_usuario
 * @property int $cantidad
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class NuevaEntradaFolio extends Model
{
	protected $table = 'nueva_entrada_folio';
	protected $primaryKey = 'id_nueva_entrada_folio';

	protected $casts = [
		'id_folio' => 'int',
		'id_tipo_entrada' => 'int',
		'id_tipo_concepto' => 'int',
		'monto' => 'float',
		'fecha' => 'datetime',
		'id_usuario' => 'int',
		'cantidad' => 'int'
	];

	protected $fillable = [
		'id_folio',
		'id_tipo_entrada',
		'id_tipo_concepto',
		'descripcion',
		'monto',
		'fecha',
		'id_usuario',
		'cantidad'
	];

	public function id_folio()
	{
		return $this->belongsTo(Folio::class, 'id_folio');
	}

	public function id_tipo_concepto()
	{
		return $this->belongsTo(TipoConcepto::class, 'id_tipo_concepto');
	}

	public function id_tipo_entrada()
	{
		return $this->belongsTo(TipoEntrada::class, 'id_tipo_entrada');
	}

	public function id_usuario()
	{
		return $this->belongsTo(User::class, 'id_usuario');
	}
}
