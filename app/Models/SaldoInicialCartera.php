<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaldoInicialCartera extends Model
{
    protected $table = 'saldos_iniciales_cartera';

    protected $fillable = [
        'cliente_id',
        'fecha_documento',
        'valor_original',
        'saldo_pendiente',
        'estado',
        'observacion',
        'user_id',
        'fecha_anulacion',
        'user_anulacion_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_documento' => 'date',
            'valor_original' => 'decimal:2',
            'saldo_pendiente' => 'decimal:2',
            'fecha_anulacion' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function usuarioAnulacion(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_anulacion_id'
        );
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(
            AplicacionAbonoSaldoInicial::class,
            'saldo_inicial_id'
        );
    }
}