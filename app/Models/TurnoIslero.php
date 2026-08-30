<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class TurnoIslero extends Model
{
    protected $table = 'turnos_islero';

    protected $fillable = [
        'estacion_id',
        'user_id',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'total_ventas_combustible',
        'total_ventas_lubricantes',
        'total_creditos',
        'total_abonos',
        'pagos_qr',
        'pagos_datafono',
        'pagos_transferencia',
        'pagos_consignacion',
        'pagos_efectivo',
        'otros_movimientos',
        'otros_movimientos_detalle',
        'total_reportado',
        'total_sistema',
        'balance_final',
        'observacion_apertura',
        'observacion_cierre',
        'datos_cierre_pendiente',
        'observacion_devolucion',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'total_ventas_combustible' => 'decimal:2',
        'total_ventas_lubricantes' => 'decimal:2',
        'total_creditos' => 'decimal:2',
        'total_abonos' => 'decimal:2',
        'pagos_qr' => 'decimal:2',
        'pagos_datafono' => 'decimal:2',
        'pagos_transferencia' => 'decimal:2',
        'pagos_consignacion' => 'decimal:2',
        'pagos_efectivo' => 'decimal:2',
        'otros_movimientos' => 'decimal:2',
        'total_reportado' => 'decimal:2',
        'total_sistema' => 'decimal:2',
        'balance_final' => 'decimal:2',
        'datos_cierre_pendiente' => 'array',
    ];

    public function estacion(): BelongsTo
    {
        return $this->belongsTo(Estacion::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(LecturaManguera::class, 'turno_islero_id');
    }

    public function mangueras(): BelongsToMany
    {
        return $this->belongsToMany(
            Manguera::class,
            'turno_islero_mangueras',
            'turno_islero_id',
            'manguera_id'
        )->withTimestamps();
    }
}