<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResolucionFacturacion extends Model
{
    protected $table = 'resoluciones_facturacion';

    protected $fillable = [
        'configuracion_empresa_id',
        'tipo_documento',
        'prefijo',
        'numero_resolucion',
        'fecha_resolucion',
        'rango_desde',
        'rango_hasta',
        'consecutivo_actual',
        'fecha_vencimiento',
        'clave_tecnica',
        'proveedor',
        'ambiente',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fecha_resolucion' => 'date',
        'fecha_vencimiento' => 'date',
        'rango_desde' => 'integer',
        'rango_hasta' => 'integer',
        'consecutivo_actual' => 'integer',
    ];

    public function configuracionEmpresa(): BelongsTo
    {
        return $this->belongsTo(ConfiguracionEmpresa::class, 'configuracion_empresa_id');
    }
}
