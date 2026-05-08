<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonoCartera extends Model
{
    protected $table = 'abonos_cartera';

    protected $fillable = [
        'cliente_id',
        'caja_id',
        'fecha_abono',
        'valor',
        'medio_pago',
        'observacion',
        'estado',
        'user_id',
        'turno_islero_id',
    ];

    protected $casts = [
        'fecha_abono' => 'date',
        'valor' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function turnoIslero(): BelongsTo
    {
        return $this->belongsTo(TurnoIslero::class, 'turno_islero_id');
    }
}