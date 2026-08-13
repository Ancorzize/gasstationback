<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicacionAbonoSaldoInicial extends Model
{
    protected $table = 'aplicaciones_abono_saldo_inicial';

    protected $fillable = [
        'abono_cartera_id',
        'saldo_inicial_id',
        'valor_aplicado',
    ];

    protected function casts(): array
    {
        return [
            'valor_aplicado' => 'decimal:2',
        ];
    }

    public function abono(): BelongsTo
    {
        return $this->belongsTo(
            AbonoCartera::class,
            'abono_cartera_id'
        );
    }

    public function saldoInicial(): BelongsTo
    {
        return $this->belongsTo(
            SaldoInicialCartera::class,
            'saldo_inicial_id'
        );
    }
}