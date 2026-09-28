<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellidos',
        'documento',
        'telefono_uno',
        'telefono_dos',
        'email',
        'direccion',
        'is_active',
        'maneja_credito',
        'cupo_credito',
        'dias_credito',
        'saldo_credito',
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
            'maneja_credito' => 'boolean',
            'cupo_credito' => 'decimal:2',
            'saldo_credito' => 'decimal:2',
            'dias_credito' => 'integer',
        ];
    }

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function movimientosCartera(): HasMany
    {
        return $this->hasMany(MovimientoCartera::class);
    }

    public function abonosCartera(): HasMany
    {
        return $this->hasMany(AbonoCartera::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}