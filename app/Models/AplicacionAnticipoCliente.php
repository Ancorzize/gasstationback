<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicacionAnticipoCliente extends Model
{
    protected $table = 'aplicaciones_anticipo_cliente';

    protected $fillable = [
        'anticipo_cliente_id',
        'venta_id',
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
        return $this->belongsTo(AnticipoCliente::class, 'anticipo_cliente_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
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
