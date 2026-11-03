<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicacionAnticipoCompra extends Model
{
    protected $table = 'aplicaciones_anticipo_compra';

    protected $fillable = [
        'anticipo_proveedor_id',
        'compra_id',
        'monto_aplicado',
        'saldo_anterior',
        'saldo_nuevo',
        'estado',
        'observacion',
        'user_id',
        'revertido_at',
        'revertido_by',
    ];

    protected $casts = [
        'monto_aplicado' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_nuevo' => 'decimal:2',
        'revertido_at' => 'datetime',
    ];

    public function anticipo(): BelongsTo
    {
        return $this->belongsTo(AnticipoProveedor::class, 'anticipo_proveedor_id');
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function usuarioReversion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revertido_by');
    }
}
