<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCartera extends Model
{
    protected $table = 'movimientos_cartera';

    protected $fillable = [
        'cliente_id',
        'tipo_movimiento',
        'origen_modulo',
        'origen_id',
        'valor',
        'saldo_anterior',
        'saldo_nuevo',
        'medio_pago',
        'descripcion',
        'user_id',
        'fecha_movimiento',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_nuevo' => 'decimal:2',
        'fecha_movimiento' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}