<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaldoInicialProveedor extends Model
{
    protected $table = 'saldos_iniciales_proveedor';

    protected $fillable = [
        'proveedor_id',
        'numero_documento',
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

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(
            Proveedor::class,
            'proveedor_id'
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
            AplicacionSaldoInicialProveedor::class,
            'saldo_inicial_id'
        );
    }
}
