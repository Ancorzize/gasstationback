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