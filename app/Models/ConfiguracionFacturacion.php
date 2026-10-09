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
        'matias_api_token',
        'matias_sandbox_url',
        'matias_ds_real_transmission',
    ];

    protected $casts = [
        'facturacion_electronica_activa' => 'boolean',
        'facturar_ventas_pos' => 'boolean',
        'facturar_ventas_combustible' => 'boolean',
        'permitir_ventas_sin_datos_fiscales' => 'boolean',
        'reintentos_automaticos' => 'boolean',
        'max_reintentos' => 'integer',
        'matias_api_token' => 'encrypted',
        'matias_ds_real_transmission' => 'boolean',
    ];

    public function configuracionEmpresa(): BelongsTo
    {
        return $this->belongsTo(ConfiguracionEmpresa::class, 'configuracion_empresa_id');
    }

    /**
     * Obtener el token de MATIAS API efectivo (BD cifrado > .env fallback).
     */
    public function getEffectiveMatiasToken(): ?string
    {
        return $this->matias_api_token ?: config('services.matias.token');
    }

    /**
     * Obtener la URL de Sandbox de MATIAS efectiva (BD > .env fallback).
     */
    public function getEffectiveMatiasSandboxUrl(): string
    {
        return $this->matias_sandbox_url ?: config('services.matias.sandbox_url', 'https://sandbox-api.maticerts.com');
    }

    /**
     * Obtener la bandera de transmisión real de DS efectiva (BD > .env fallback).
     */
    public function getEffectiveMatiasDsRealTransmission(): bool
    {
        return $this->matias_ds_real_transmission ?? (bool) config('services.matias.ds_real_transmission', false);
    }
}
