<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent para la tabla mapeos_catalogos_facturacion.
 * Almacena las equivalencias entre los códigos/tipos del ERP y los catálogos externos (MATIAS/DIAN).
 */
class MapeoCatalogoFacturacion extends Model
{
    protected $table = 'mapeos_catalogos_facturacion';

    protected $fillable = [
        'proveedor',
        'categoria',
        'codigo_interno',
        'codigo_externo',
        'codigo_externo_secundario',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
