<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bomba extends Model
{
    protected $table = 'bombas';

    protected $fillable = [
        'estacion_id',
        'nombre',
        'codigo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function estacion(): BelongsTo
    {
        return $this->belongsTo(Estacion::class);
    }

    public function mangueras(): HasMany
    {
        return $this->hasMany(Manguera::class);
    }
}