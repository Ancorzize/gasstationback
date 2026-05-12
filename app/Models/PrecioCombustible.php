<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioCombustible extends Model
{
    protected $table = 'precios_combustible';

    protected $fillable = [
        'producto_id',
        'precio',
        'fecha_inicio',
        'fecha_fin',
        'is_active',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}