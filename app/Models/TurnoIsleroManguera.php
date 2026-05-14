<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoIsleroManguera extends Model
{
    protected $table = 'turno_islero_mangueras';

    protected $fillable = [
        'turno_islero_id',
        'manguera_id',
    ];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(TurnoIslero::class, 'turno_islero_id');
    }

    public function manguera(): BelongsTo
    {
        return $this->belongsTo(Manguera::class);
    }
}