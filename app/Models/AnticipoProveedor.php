<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnticipoProveedor extends Model
{
    protected $table = 'anticipos_proveedor';

    protected $fillable = [
        'numero',
        'proveedor_id',
        'caja_id',
        'movimiento_caja_id',
        'pago_compra_id',
        'origen',
        'monto_original',
        'saldo_disponible',
        'estado',
        'metodo_pago',
        'referencia',
        'observacion',
        'user_id',
    ];

    protected $casts = [
        'monto_original' => 'decimal:2',
        'saldo_disponible' => 'decimal:2',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function movimientoCaja(): BelongsTo
    {
        return $this->belongsTo(MovimientoCaja::class, 'movimiento_caja_id');
    }

    public function pagoCompra(): BelongsTo
    {
        return $this->belongsTo(PagoCompra::class, 'pago_compra_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(AplicacionAnticipoCompra::class, 'anticipo_proveedor_id');
    }
}
