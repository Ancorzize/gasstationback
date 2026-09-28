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
     * Relación opcional con la Venta de origen.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    /**
     * Relación opcional con la Configuración de Facturación utilizada.
     */
    public function configuracionFacturacion(): BelongsTo
    {
        return $this->belongsTo(ConfiguracionFacturacion::class, 'configuracion_facturacion_id');
    }
}
