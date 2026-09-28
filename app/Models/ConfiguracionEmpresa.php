<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConfiguracionEmpresa extends Model
{
    protected $table = 'configuracion_empresa';

    protected $fillable = [
        'nombre_empresa',
        'nombre_comercial',
        'nit',
        'dv',
        'email',
        'telefono',
        'direccion',
        'tipo_persona',
        'tipo_documento',
        'pais_id',
        'departamento_id',
        'ciudad_id',
        'codigo_postal',
        'matricula_mercantil',
        'logo_url',
        'responsable_iva',
        'regimen',
        'tipo_regimen',
        'responsabilidades_fiscales',
        'porcentaje_iva',
        'maneja_iva_incluido',
        'prefijo_factura',
        'numero_resolucion',
        'fecha_resolucion',
        'rango_desde',
        'rango_hasta',
        'fecha_vencimiento',
        'moneda',
        'simbolo_moneda',
        'decimales',
        'logo_base64',
        'logo_mime_type',
    ];

    protected $casts = [
        'responsable_iva' => 'boolean',
        'porcentaje_iva' => 'decimal:2',
        'maneja_iva_incluido' => 'boolean',
        'fecha_resolucion' => 'date',
        'fecha_vencimiento' => 'date',
        'decimales' => 'integer',
        'responsabilidades_fiscales' => 'array',
    ];

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class);
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function configuracionFacturacion(): HasOne
    {
        return $this->hasOne(ConfiguracionFacturacion::class, 'configuracion_empresa_id');
    }
}