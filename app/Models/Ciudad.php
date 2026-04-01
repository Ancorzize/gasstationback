<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ciudad extends Model
{
    protected $table = 'ciudades';

    protected $fillable = [
        'departamento_id',
        'nombre',
        'codigo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }
}