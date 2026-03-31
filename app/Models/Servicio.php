<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'precio',
        'unidad_medida_id',
        'permite_decimal',
        'duracion_minutos',
        'is_active',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'permite_decimal' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }
}