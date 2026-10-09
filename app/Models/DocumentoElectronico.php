<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent para la tabla documentos_electronicos.
 */
class DocumentoElectronico extends Model
{
    protected $table = 'documentos_electronicos';

    protected $fillable = [
        'venta_id',
        'compra_id',
        'gasto_id',
        'payroll_settlement_id',
        'proveedor_id',
        'configuracion_facturacion_id',
        'tipo_documento',
        'estado',
        'proveedor',
        'ambiente',
        'identificador_externo',
        'cufe',
        'numero_documento',
        'prefijo',
        'track_id',
        'mensaje',
        'errores',
        'datos_tecnicos',
    ];

    protected $casts = [
        'errores' => 'array',
        'datos_tecnicos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación opcional con la Liquidación de Nómina de origen.
     */
    public function payrollSettlement(): BelongsTo
    {
        return $this->belongsTo(PayrollSettlement::class, 'payroll_settlement_id');
    }

    /**
     * Relación opcional con la Venta de origen.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    /**
     * Relación opcional con la Compra de origen.
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    /**
     * Relación opcional con el Gasto de origen.
     */
    public function gasto(): BelongsTo
    {
        return $this->belongsTo(Gasto::class, 'gasto_id');
    }

    /**
     * Relación opcional con el Proveedor asociado.
     */
    public function proveedorRelacion(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Relación opcional con la Configuración de Facturación utilizada.
     */
    public function configuracionFacturacion(): BelongsTo
    {
        return $this->belongsTo(ConfiguracionFacturacion::class, 'configuracion_facturacion_id');
    }
}
