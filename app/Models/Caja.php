<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    protected $table = 'cajas';

    protected $fillable = [
        'fecha_apertura',
        'fecha_cierre',
        'monto_apertura',
        'monto_cierre_sistema',
        'monto_cierre_real',
        'diferencia_cierre',
        'estado',
        'user_apertura_id',
        'user_cierre_id',
        'observacion_apertura',
        'observacion_cierre',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'monto_apertura' => 'decimal:2',
        'monto_cierre_sistema' => 'decimal:2',
        'monto_cierre_real' => 'decimal:2',
        'diferencia_cierre' => 'decimal:2',
    ];

    public function usuarioApertura(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_apertura_id');
    }

    public function usuarioCierre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_cierre_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class);
    }
}