<?php

namespace App\Modules\TurnosIslero\Application\Services;

use App\Models\TurnoIslero;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\TurnosIslero\Application\DTOs\AbrirTurnoIsleroDTO;
use App\Modules\TurnosIslero\Application\DTOs\CerrarTurnoIsleroDTO;
use App\Modules\TurnosIslero\Application\Interfaces\TurnoIsleroRepositoryInterface;

class TurnoIsleroService
{
    public function __construct(
        protected TurnoIsleroRepositoryInterface $turnoRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->turnoRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): TurnoIslero
    {
        $turno = $this->turnoRepository->findById($id);

        if (!$turno) {
            throw new HttpException(404, 'Turno no encontrado.');
        }

        return $turno;
    }

    public function actual(int $userId): ?TurnoIslero
    {
        return $this->turnoRepository->getTurnoAbiertoByUser($userId);
    }

    public function abrir(AbrirTurnoIsleroDTO $dto): TurnoIslero
    {
        return DB::transaction(function () use ($dto) {
            if ($this->turnoRepository->existsTurnoAbiertoByUser($dto->user_id)) {
                throw new HttpException(422, 'Ya tienes un turno abierto.');
            }

            $manguerasSeleccionadas = $this->turnoRepository->getManguerasByIds($dto->mangueras);

            if ($manguerasSeleccionadas->count() !== count(array_unique($dto->mangueras))) {
                throw new HttpException(422, 'Una o más mangueras seleccionadas no existen.');
            }

            foreach ($manguerasSeleccionadas as $manguera) {
                if (!(bool) $manguera->is_active) {
                    throw new HttpException(422, "La manguera {$manguera->nombre} está inactiva.");
                }

                if ((int) $manguera->bomba?->estacion_id !== (int) $dto->estacion_id) {
                    throw new HttpException(422, "La manguera {$manguera->nombre} no pertenece a la estación seleccionada.");
                }
            }

            $ocupadas = $this->turnoRepository
                ->getManguerasOcupadasEnTurnosAbiertos($dto->estacion_id)
                ->toArray();

            $manguerasOcupadasSeleccionadas = array_values(array_intersect($dto->mangueras, $ocupadas));

            if (count($manguerasOcupadasSeleccionadas) > 0) {
                throw new HttpException(422, 'Una o más mangueras seleccionadas ya están asignadas a un turno abierto.');
            }

            $lecturasFaltantes = $this->getLecturasInicialesFaltantes(
                $manguerasSeleccionadas,
                $dto->lecturas_iniciales
            );

            if (count($lecturasFaltantes) > 0) {
                throw ValidationException::withMessages([
                    'lecturas_iniciales_faltantes' => $lecturasFaltantes,
                ]);
            }

            $turno = $this->turnoRepository->createTurno([
                'estacion_id' => $dto->estacion_id,
                'user_id' => $dto->user_id,
                'fecha_apertura' => now(),
                'fecha_cierre' => null,
                'estado' => 'abierto',
                'observacion_apertura' => $dto->observacion_apertura,
            ]);

            $this->turnoRepository->asignarMangueras($turno, $dto->mangueras);

            foreach ($manguerasSeleccionadas as $manguera) {
                $lecturaInicial = $this->resolverLecturaInicial(
                    $manguera->id,
                    $dto->lecturas_iniciales
                );

                $precioVigente = $this->turnoRepository->getPrecioVigenteProducto(
                    $manguera->producto_id
                );

                if ($precioVigente === null) {
                    throw new HttpException(
                        422,
                        "El producto {$manguera->producto?->nombre} no tiene precio de combustible vigente."
                    );
                }

                $this->turnoRepository->createLectura([
                    'turno_islero_id' => $turno->id,
                    'manguera_id' => $manguera->id,
                    'lectura_inicial' => $lecturaInicial,
                    'lectura_final' => null,
                    'galones_vendidos' => 0,
                    'precio_galon' => $precioVigente,
                    'total_venta' => 0,
                ]);
            }

            return $this->findById($turno->id);
        });
    }

    private function getLecturasInicialesFaltantes($mangueras, array $lecturasIniciales): array
    {
        $faltantes = [];

        foreach ($mangueras as $manguera) {
            $tieneLecturaEnRequest = collect($lecturasIniciales)
                ->contains(fn ($item) => (int) $item['manguera_id'] === (int) $manguera->id);

            $ultimaLectura = $this->turnoRepository->getUltimaLecturaCerradaByManguera($manguera->id);
            $tieneLecturaAnterior = $ultimaLectura && $ultimaLectura->lectura_final !== null;

            if (!$tieneLecturaEnRequest && !$tieneLecturaAnterior) {
                $faltantes[] = [
                    'manguera_id' => $manguera->id,
                    'codigo' => $manguera->codigo,
                    'nombre' => $manguera->nombre,
                    'producto' => $manguera->producto ? [
                        'id' => $manguera->producto->id,
                        'codigo' => $manguera->producto->codigo,
                        'nombre' => $manguera->producto->nombre,
                    ] : null,
                    'bomba' => $manguera->bomba ? [
                        'id' => $manguera->bomba->id,
                        'codigo' => $manguera->bomba->codigo,
                        'nombre' => $manguera->bomba->nombre,
                    ] : null,
                ];
            }
        }

        return $faltantes;
    }

    private function resolverLecturaInicial(int $mangueraId, array $lecturasIniciales): float
    {
        foreach ($lecturasIniciales as $item) {
            if ((int) $item['manguera_id'] === $mangueraId) {
                return (float) $item['lectura_inicial'];
            }
        }

        $ultimaLectura = $this->turnoRepository->getUltimaLecturaCerradaByManguera($mangueraId);

        if ($ultimaLectura && $ultimaLectura->lectura_final !== null) {
            return (float) $ultimaLectura->lectura_final;
        }

        throw new HttpException(
            422,
            "Debe enviar lectura inicial para la manguera {$mangueraId}, porque no tiene lectura anterior."
        );
    }

    public function cerrar(CerrarTurnoIsleroDTO $dto): TurnoIslero
    {
        return DB::transaction(function () use ($dto) {
            $turno = $this->findById($dto->turno_id);

            if ($turno->estado !== 'abierto') {
                throw new HttpException(422, 'Solo se pueden cerrar turnos abiertos.');
            }

            if ((int) $turno->user_id !== (int) $dto->user_id) {
                throw new HttpException(403, 'No puedes cerrar un turno de otro usuario.');
            }

            $manguerasAsignadas = $turno->lecturas
                ->pluck('manguera_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            $manguerasEnviadas = collect($dto->lecturas_finales)
                ->pluck('manguera_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            $faltantes = array_values(array_diff($manguerasAsignadas, $manguerasEnviadas));

            if (count($faltantes) > 0) {
                throw ValidationException::withMessages([
                    'lecturas_finales_faltantes' => $faltantes,
                ]);
            }

            $noAsignadas = array_values(array_diff($manguerasEnviadas, $manguerasAsignadas));

            if (count($noAsignadas) > 0) {
                throw new HttpException(422, 'Una o más mangueras enviadas no pertenecen a este turno.');
            }

            foreach ($dto->lecturas_finales as $item) {
                $mangueraId = (int) $item['manguera_id'];
                $lecturaFinal = (float) $item['lectura_final'];

                $lectura = $this->turnoRepository->findLecturaByTurnoAndManguera(
                    $turno->id,
                    $mangueraId
                );

                if (!$lectura) {
                    throw new HttpException(422, "La manguera {$mangueraId} no pertenece a este turno.");
                }

                if ($lecturaFinal < (float) $lectura->lectura_inicial) {
                    throw new HttpException(
                        422,
                        "La lectura final no puede ser menor que la inicial en la manguera {$mangueraId}."
                    );
                }

                $galonesVendidos = $lecturaFinal - (float) $lectura->lectura_inicial;
                $totalVentaFisica = $galonesVendidos * (float) $lectura->precio_galon;

                $this->turnoRepository->updateLectura($lectura, [
                    'lectura_final' => $lecturaFinal,
                    'galones_vendidos' => $galonesVendidos,
                    'total_venta' => $totalVentaFisica,
                ]);
            }

            $totalVentasCombustible = $this->turnoRepository->sumVentasCombustibleByTurno($turno->id);
            $totalVentasLubricantes = $this->turnoRepository->sumVentasLubricantesByTurno($turno->id);
            $totalCreditos = $this->turnoRepository->sumVentasCreditoByTurno($turno->id);
            $totalAbonos = $this->turnoRepository->sumAbonosByTurno($turno->id);

            $totalReportado =
                $dto->pagos_qr +
                $dto->pagos_datafono +
                $dto->pagos_transferencia +
                $dto->pagos_consignacion +
                $dto->pagos_efectivo +
                $totalCreditos +
                $dto->otros_movimientos;

            $totalSistema =
                $totalVentasCombustible +
                $totalVentasLubricantes +
                $totalAbonos;

            $balanceFinal = $totalSistema - $totalReportado;

            $this->turnoRepository->updateTurno($turno, [
                'fecha_cierre' => now(),
                'estado' => 'cerrado',

                'total_ventas_combustible' => $totalVentasCombustible,
                'total_ventas_lubricantes' => $totalVentasLubricantes,
                'total_creditos' => $totalCreditos,
                'total_abonos' => $totalAbonos,

                'pagos_qr' => $dto->pagos_qr,
                'pagos_datafono' => $dto->pagos_datafono,
                'pagos_transferencia' => $dto->pagos_transferencia,
                'pagos_consignacion' => $dto->pagos_consignacion,
                'pagos_efectivo' => $dto->pagos_efectivo,
                'otros_movimientos' => $dto->otros_movimientos,
                'otros_movimientos_detalle' => $dto->otros_movimientos_detalle,

                'total_reportado' => $totalReportado,
                'total_sistema' => $totalSistema,
                'balance_final' => $balanceFinal,

                'observacion_cierre' => $dto->observacion_cierre,
            ]);

            return $this->findById($turno->id);
        });
    }

    public function resumenCierre(int $id): array
    {
        $turno = $this->findById($id);

        if ($turno->estado !== 'abierto') {
            throw new HttpException(422, 'Solo se puede consultar resumen de cierre para turnos abiertos.');
        }

        $totalVentasCombustible = $this->turnoRepository->sumVentasCombustiblePagadasByTurno($turno->id);
        $totalVentasLubricantes = $this->turnoRepository->sumVentasLubricantesPagadasByTurno($turno->id);
        $totalCreditos = $this->turnoRepository->sumVentasCreditoByTurno($turno->id);
        $totalAbonos = $this->turnoRepository->sumAbonosByTurno($turno->id);

        $pagosEfectivo = $this->turnoRepository->sumPagosVentasByTurnoAndMetodo($turno->id, 'efectivo')
            + $this->turnoRepository->sumAbonosByTurnoAndMetodo($turno->id, 'efectivo');

        $pagosDatafono = $this->turnoRepository->sumPagosVentasByTurnoAndMetodo($turno->id, 'datafono')
            + $this->turnoRepository->sumAbonosByTurnoAndMetodo($turno->id, 'datafono');

        $pagosQr = $this->turnoRepository->sumPagosVentasByTurnoAndMetodo($turno->id, 'qr')
            + $this->turnoRepository->sumAbonosByTurnoAndMetodo($turno->id, 'qr');

        $pagosTransferencia = $this->turnoRepository->sumPagosVentasByTurnoAndMetodo($turno->id, 'transferencia')
            + $this->turnoRepository->sumAbonosByTurnoAndMetodo($turno->id, 'transferencia');

        $pagosConsignacion = $this->turnoRepository->sumPagosVentasByTurnoAndMetodo($turno->id, 'consignacion')
            + $this->turnoRepository->sumAbonosByTurnoAndMetodo($turno->id, 'consignacion');

        $totalPagosReportadosSugeridos =
            $pagosEfectivo +
            $pagosDatafono +
            $pagosQr +
            $pagosTransferencia +
            $pagosConsignacion +
            $totalCreditos;

        $totalSistema =
            $totalVentasCombustible +
            $totalVentasLubricantes +
            $totalAbonos;

        $lecturas = $turno->lecturas->map(function ($lectura) use ($turno) {
            $precioGalon = (float) $lectura->precio_galon;

            $galonesVendidosSistema = $this->turnoRepository
                ->sumGalonesCombustibleByTurnoAndManguera(
                    $turno->id,
                    $lectura->manguera_id
                );

            $totalVentaSistema = $this->turnoRepository
                ->sumTotalCombustibleByTurnoAndManguera(
                    $turno->id,
                    $lectura->manguera_id
                );

            $lecturaSugerida = (float) $lectura->lectura_inicial + $galonesVendidosSistema;

            return [
                'id' => $lectura->id,
                'manguera_id' => $lectura->manguera_id,

                'manguera' => $lectura->manguera ? [
                    'id' => $lectura->manguera->id,
                    'nombre' => $lectura->manguera->nombre,
                    'codigo' => $lectura->manguera->codigo,
                    'bomba' => $lectura->manguera->bomba ? [
                        'id' => $lectura->manguera->bomba->id,
                        'nombre' => $lectura->manguera->bomba->nombre,
                        'codigo' => $lectura->manguera->bomba->codigo,
                    ] : null,
                    'producto' => $lectura->manguera->producto ? [
                        'id' => $lectura->manguera->producto->id,
                        'codigo' => $lectura->manguera->producto->codigo,
                        'nombre' => $lectura->manguera->producto->nombre,
                    ] : null,
                ] : null,

                'lectura_inicial' => (float) $lectura->lectura_inicial,
                'lectura_final' => $lectura->lectura_final !== null
                    ? (float) $lectura->lectura_final
                    : null,

                'precio_galon' => $precioGalon,

                'galones_vendidos_sistema' => round($galonesVendidosSistema, 3),
                'total_venta_sistema' => round($totalVentaSistema, 2),
                'lectura_sugerida' => round($lecturaSugerida, 3),

                'galones_vendidos' => (float) $lectura->galones_vendidos,
                'total_venta' => (float) $lectura->total_venta,
            ];
        })->values();

        return [
            'turno' => [
                'id' => $turno->id,
                'estado' => $turno->estado,
                'fecha_apertura' => $turno->fecha_apertura,
                'estacion' => $turno->estacion ? [
                    'id' => $turno->estacion->id,
                    'nombre' => $turno->estacion->nombre,
                    'codigo' => $turno->estacion->codigo,
                ] : null,
                'usuario' => $turno->usuario ? [
                    'id' => $turno->usuario->id,
                    'name' => $turno->usuario->name,
                    'email' => $turno->usuario->email,
                ] : null,
            ],

            'lecturas' => $lecturas,

            'totales_pago_sugeridos' => [
                'efectivo' => $pagosEfectivo,
                'datafono' => $pagosDatafono,
                'qr' => $pagosQr,
                'transferencia' => $pagosTransferencia,
                'consignacion' => $pagosConsignacion,
                'creditos' => $totalCreditos,
                'total_reportado_sugerido' => $totalPagosReportadosSugeridos,
            ],

            'totales_sistema' => [
                'ventas_combustible' => $totalVentasCombustible,
                'ventas_lubricantes' => $totalVentasLubricantes,
                'creditos' => $totalCreditos,
                'abonos' => $totalAbonos,
                'total_sistema' => $totalSistema,
            ],

            'balance_preliminar' => $totalSistema - $totalPagosReportadosSugeridos,

            'nota' => 'La lectura sugerida se calcula con las ventas de combustible registradas por manguera. El islero debe confirmarla o corregirla con la lectura física real.',
        ];
    }

    public function manguerasDisponibles(int $estacionId): array
    {
        $mangueras = $this->turnoRepository->getManguerasDisponiblesByEstacion($estacionId);

        return $mangueras->map(function ($manguera) {
            $ultimaLectura = $this->turnoRepository->getUltimaLecturaCerradaByManguera($manguera->id);

            return [
                'id' => $manguera->id,
                'codigo' => $manguera->codigo,
                'nombre' => $manguera->nombre,
                'bomba' => $manguera->bomba ? [
                    'id' => $manguera->bomba->id,
                    'codigo' => $manguera->bomba->codigo,
                    'nombre' => $manguera->bomba->nombre,
                ] : null,
                'producto' => $manguera->producto ? [
                    'id' => $manguera->producto->id,
                    'codigo' => $manguera->producto->codigo,
                    'nombre' => $manguera->producto->nombre,
                ] : null,
                'ultima_lectura_final' => $ultimaLectura ? (float) $ultimaLectura->lectura_final : null,
                'requiere_lectura_inicial' => !$ultimaLectura || $ultimaLectura->lectura_final === null,
            ];
        })->values()->toArray();
    }
}