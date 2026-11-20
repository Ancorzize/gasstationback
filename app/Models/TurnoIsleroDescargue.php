<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoIsleroDescargue extends Model
{
    protected $table = 'turno_islero_descargues';

    protected $fillable = [
        'turno_islero_id',
        'user_id',
        'monto',
        'fecha_descargue',
        'observacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_descargue' => 'datetime',
    ];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(TurnoIslero::class, 'turno_islero_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
