<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnticipoCliente extends Model
{
    protected $table = 'anticipos_cliente';

    protected $fillable = [
        'numero',
        'cliente_id',
        'caja_id',
        'abono_cartera_id',
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

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function abonoCartera(): BelongsTo
    {
        return $this->belongsTo(AbonoCartera::class, 'abono_cartera_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(AplicacionAnticipoCliente::class, 'anticipo_cliente_id');
    }
}
