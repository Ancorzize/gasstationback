<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'nit',
        'telefono',
        'direccion',
        'email',
        'is_active',
        'tipo_persona',
        'tipo_documento_id',
        'tipo_organization_id',
        'tax_regime_id',
        'tax_level_id',
        'codigo_postal',
        'ciudad_id',
        'pais_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'tipo_organization_id' => 'integer',
            'tax_regime_id' => 'integer',
            'tax_level_id' => 'integer',
            'ciudad_id' => 'integer',
            'pais_id' => 'integer',
        ];
    }

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id');
    }

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }
}