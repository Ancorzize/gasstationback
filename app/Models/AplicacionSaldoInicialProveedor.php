<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicacionSaldoInicialProveedor extends Model
{
    protected $table = 'aplicaciones_saldo_inicial_proveedor';

    protected $fillable = [
        'saldo_inicial_id',
        'movimiento_caja_id',
        'valor_aplicado',
        'saldo_anterior',
        'saldo_nuevo',
        'user_id',
        'fecha_aplicacion',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'valor_aplicado' => 'decimal:2',
            'saldo_anterior' => 'decimal:2',
            'saldo_nuevo' => 'decimal:2',
            'fecha_aplicacion' => 'date',
        ];
    }

    public function saldoInicial(): BelongsTo
    {
        return $this->belongsTo(
            SaldoInicialProveedor::class,
            'saldo_inicial_id'
        );
    }

    public function movimientoCaja(): BelongsTo
    {
        return $this->belongsTo(
            MovimientoCaja::class,
            'movimiento_caja_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
