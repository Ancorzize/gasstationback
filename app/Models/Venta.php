<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'prefijo',
        'numero_factura',
        'cliente_id',
        'user_id',
        'tipo_venta',
        'estado',
        'estado_pago',
        'subtotal',
        'descuento',
        'impuesto',
        'soldicom',
        'sobre_tasa',
        'total',
        'total_pagado',
        'saldo_pendiente',
        'fecha_venta',
        'observacion',
        'motivo_anulacion',
        'user_anulacion_id',
        'fecha_anulacion',
        'turno_islero_id',
        'tipo_origen',
        'bodega_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'soldicom' => 'decimal:2',
        'sobre_tasa' => 'decimal:2',
        'total' => 'decimal:2',
        'total_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'fecha_venta' => 'datetime',
        'fecha_anulacion' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVenta::class);
    }

    public function usuarioAnulacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_anulacion_id');
    }

    public function turnoIslero(): BelongsTo
    {
        return $this->belongsTo(TurnoIslero::class, 'turno_islero_id');
    }
}