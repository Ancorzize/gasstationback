<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionFacturacion extends Model
{
    protected $table = 'configuracion_facturacion';

    protected $fillable = [
        'configuracion_empresa_id',
        'proveedor_activo',
        'ambiente',
        'facturacion_electronica_activa',
        'facturar_ventas_pos',
        'facturar_ventas_combustible',
        'permitir_ventas_sin_datos_fiscales',
        'reintentos_automaticos',
        'max_reintentos',
    ];

    protected $casts = [
        'facturacion_electronica_activa' => 'boolean',
        'facturar_ventas_pos' => 'boolean',
        'facturar_ventas_combustible' => 'boolean',
        'permitir_ventas_sin_datos_fiscales' => 'boolean',
        'reintentos_automaticos' => 'boolean',
        'max_reintentos' => 'integer',
    ];

    public function configuracionEmpresa(): BelongsTo
    {
        return $this->belongsTo(ConfiguracionEmpresa::class, 'configuracion_empresa_id');
    }
}
