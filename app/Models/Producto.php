<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    public const TIPO_IMPUESTO_GRAVADO = 'gravado';
    public const TIPO_IMPUESTO_EXENTO = 'exento';
    public const TIPO_IMPUESTO_EXCLUIDO = 'excluido';

    public const TIPOS_IMPUESTO_PERMITIDOS = [
        self::TIPO_IMPUESTO_GRAVADO,
        self::TIPO_IMPUESTO_EXENTO,
        self::TIPO_IMPUESTO_EXCLUIDO,
    ];

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'marca_id',
        'categoria_producto_id',
        'unidad_medida_id',
        'precio_compra',
        'precio_venta',
        'tipo_impuesto',
        'porcentaje_iva',
        'codigo_impuesto',
        'permite_decimal',
        'is_active',
        'codigo_barras',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'porcentaje_iva' => 'decimal:2',
        'permite_decimal' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tieneClasificacionTributariaValida(): bool
    {
        if (!in_array($this->tipo_impuesto, self::TIPOS_IMPUESTO_PERMITIDOS, true)) {
            return false;
        }

        if ($this->porcentaje_iva === null || !is_numeric($this->porcentaje_iva)) {
            return false;
        }

        $tarifa = round((float) $this->porcentaje_iva, 2);

        if ($this->tipo_impuesto === self::TIPO_IMPUESTO_GRAVADO) {
            return $tarifa > 0;
        }

        return $tarifa === 0.0;
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function categoriaProducto(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_producto_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function preciosCombustible(): HasMany
    {
        return $this->hasMany(PrecioCombustible::class);
    }
    
    public function inventarios(): HasMany
    {
        return $this->hasMany(
            Inventario::class,
            'producto_id'
        );
    }
}