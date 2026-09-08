<?php

namespace App\Modules\TurnosIslero\Application\Services;

use App\Models\TurnoIslero;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\TurnosIslero\Application\DTOs\AbrirTurnoIsleroDTO;
use App\Modules\TurnosIslero\Application\DTOs\CerrarTurnoIsleroDTO;
use App\Modules\TurnosIslero\Application\Interfaces\TurnoIsleroRepositoryInterface;
use App\Modules\Ventas\Application\Interfaces\VentaRepositoryInterface;
use App\Modules\Ventas\Application\Services\VentaService;
use App\Modules\TurnosIslero\Application\DTOs\SolicitarCierreTurnoIsleroDTO;

class TurnoIsleroService
{

    public function __construct(
        protected TurnoIsleroRepositoryInterface $turnoRepository,
        protected VentaRepositoryInterface $ventaRepository,
        protected VentaService $ventaService
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->turnoRepository->getAll($filters);
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

            $ultimaLectura = $this->turnoRepository->getUltimaLecturaFinalByManguera($manguera->id);
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

        $ultimaLectura = $this->turnoRepository->getUltimaLecturaFinalByManguera($mangueraId);

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

            $manguerasAsignadas = $turno->lecturas
                ->pluck('manguera_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            $turnoAnteriorSinCerrar = $this->turnoRepository->getTurnoAnteriorSinCerrar(
                    $turno->id,
                    $manguerasAsignadas
                );

            if ($turnoAnteriorSinCerrar) {
                throw new HttpException(
                    422,
                    "Primero debes cerrar el turno #{$turnoAnteriorSinCerrar->id}, "
                    . "que es anterior y comparte una o más mangueras."
                );
            }

            if (!$turno) {
                throw new HttpException(
                    404,
                    'Turno no encontrado.'
                );
            }

            if ($turno->estado !== 'pendiente_cierre') {
                throw new HttpException(
                    422,
                    'Solo se pueden cerrar turnos pendientes de cierre.'
                );
            }

            /*
            * El usuario que ejecuta el cierre puede ser:
            * - el mismo islero
            * - un administrador autorizado
            */

            $manguerasAsignadas = $turno->lecturas
                ->pluck('manguera_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            $manguerasEnviadas = collect($dto->lecturas_finales)
                ->pluck('manguera_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            /*
            * Validar que no falte ninguna manguera.
            */
            $faltantes = array_diff(
                $manguerasAsignadas,
                $manguerasEnviadas
            );

            if (!empty($faltantes)) {
                throw new HttpException(
                    422,
                    'Debe enviar lectura final para todas las mangueras del turno.'
                );
            }

            /*
            * Validar que no lleguen mangueras que no pertenecen al turno.
            */
            $extras = array_diff(
                $manguerasEnviadas,
                $manguerasAsignadas
            );

            if (!empty($extras)) {
                throw new HttpException(
                    422,
                    'Se enviaron mangueras que no pertenecen al turno.'
                );
            }

            /*
            * Actualizar lecturas finales y calcular combustible físico.
            */
            $totalVentasCombustibleFisica = 0;

            foreach ($dto->lecturas_finales as $item) {

                $mangueraId = (int) $item['manguera_id'];
                $lecturaFinal = (float) $item['lectura_final'];

                $lectura = $this->turnoRepository
                    ->findLecturaByTurnoAndManguera(
                        $turno->id,
                        $mangueraId
                    );

                if (!$lectura) {
                    throw new HttpException(
                        422,
                        "La manguera {$mangueraId} no pertenece al turno."
                    );
                }

                if ($lecturaFinal < (float) $lectura->lectura_inicial) {
                    throw new HttpException(
                        422,
                        "La lectura final de la manguera {$mangueraId} no puede ser menor que la lectura inicial."
                    );
                }

                $galonesVendidos = round(
                    $lecturaFinal - (float) $lectura->lectura_inicial,
                    3
                );

                $totalVentaFisica = round(
                    $galonesVendidos * (float) $lectura->precio_galon,
                    2
                );

                $this->turnoRepository->updateLectura(
                    $lectura,
                    [
                        'lectura_final' => $lecturaFinal,
                        'galones_vendidos' => $galonesVendidos,
                        'total_venta' => $totalVentaFisica,
                    ]
                );

                $totalVentasCombustibleFisica += $totalVentaFisica;

                /*
                * El AJT solo corresponde a los galones físicos
                * que exceden los galones registrados por el sistema.
                *
                * El inventario se descuenta únicamente por esa diferencia.
                */
                $galonesSistema = $this->ventaRepository
                    ->sumGalonesCombustibleByTurnoAndManguera(
                        $turno->id,
                        $mangueraId
                    );

                $galonesAjuste = round(
                    $galonesVendidos - (float) $galonesSistema,
                    3
                );

                if ($galonesAjuste > 0) {
                    $this->descontarInventarioCombustible(
                        $turno,
                        $lectura,
                        $galonesAjuste,
                        $dto->user_id
                    );
                }
            }

            $totalVentasCombustibleFisica = round(
                $totalVentasCombustibleFisica,
                2
            );

            /*
            * Crear la venta AJT después de tener las lecturas finales.
            */
            $this->ventaService->crearVentaAjusteTurno(
                $turno,
                $turno->lecturas()
                    ->with('manguera.producto.categoriaProducto')
                    ->get(),
                $dto->user_id
            );

            /*
            * Recalcular todos los valores desde las ventas actuales.
            */
            $totalVentasCombustibleSistema =
                $this->turnoRepository
                    ->sumVentasCombustibleByTurno($turno->id);

            $totalVentasLubricantes =
                $this->turnoRepository
                    ->sumVentasLubricantesByTurno($turno->id);

            $totalCreditos =
                $this->turnoRepository
                    ->sumVentasCreditoByTurno($turno->id);

            $totalAbonos =
                $this->turnoRepository
                    ->sumAbonosByTurno($turno->id);

            $totalVentasCombustibleSistema = round(
                (float) $totalVentasCombustibleSistema,
                2
            );

            $totalVentasLubricantes = round(
                (float) $totalVentasLubricantes,
                2
            );

            $totalCreditos = round(
                (float) $totalCreditos,
                2
            );

            $totalAbonos = round(
                (float) $totalAbonos,
                2
            );

            $diferenciaCombustible = round(
                $totalVentasCombustibleFisica
                - $totalVentasCombustibleSistema,
                2
            );

            /*
            * Validar recaudo enviado contra el recaudo esperado.
            */
            $this->validarRecaudoDestinos(
                $turno,
                $dto
            );

            /*
            * Consolidar pagos.
            */
            $pagos = [
                'efectivo' => 0,
                'qr' => 0,
                'datafono' => 0,
                'transferencia' => 0,
                'consignacion' => 0,
            ];

            foreach ($dto->destinos_recaudo as $destino) {

                foreach ($destino['pagos'] as $medio => $valor) {

                    if (array_key_exists($medio, $pagos)) {
                        $pagos[$medio] += (float) ($valor ?? 0);
                    }
                }
            }

            foreach ($pagos as $medio => $valor) {
                $pagos[$medio] = round($valor, 2);
            }

            /*
            * Otros movimientos.
            */
            $otrosMovimientos = round(
                (float) $dto->otros_movimientos,
                2
            );

            /*
            * Total reportado.
            */
            $totalReportado = round(
                array_sum($pagos) + $otrosMovimientos + $totalAbonos,
                2
            );

            /*
            * Total esperado.
            */
            $totalRecaudoEsperado = round(
                $totalVentasCombustibleFisica
                + $totalVentasLubricantes
                + $totalAbonos
                - $totalCreditos,
                2
            );

            /*
            * Balance final.
            */
            $balanceFinal = round(
                $totalReportado - $totalRecaudoEsperado,
                2
            );

            /*
            * ============================================================
            * GUARDAR RECAUDO FINAL APROBADO
            * ============================================================
            *
            * Se eliminan los valores que había reportado el islero
            * y se guardan nuevamente los valores corregidos por
            * el administrador.
            */
            $turno->recaudos()->delete();

            foreach ($dto->destinos_recaudo as $destino) {

                $pagosDestino = $destino['pagos'] ?? [];

                $efectivo = round(
                    (float) ($pagosDestino['efectivo'] ?? 0),
                    2
                );

                $qr = round(
                    (float) ($pagosDestino['qr'] ?? 0),
                    2
                );

                $datafono = round(
                    (float) ($pagosDestino['datafono'] ?? 0),
                    2
                );

                $transferencia = round(
                    (float) ($pagosDestino['transferencia'] ?? 0),
                    2
                );

                $consignacion = round(
                    (float) ($pagosDestino['consignacion'] ?? 0),
                    2
                );

                $total = round(
                    $efectivo
                    + $qr
                    + $datafono
                    + $transferencia
                    + $consignacion,
                    2
                );

                $turno->recaudos()->create([
                    'destino_recaudo_id' =>
                        (int) $destino['destino_recaudo_id'],

                    'efectivo' => $efectivo,
                    'qr' => $qr,
                    'datafono' => $datafono,
                    'transferencia' => $transferencia,
                    'consignacion' => $consignacion,
                    'total' => $total,
                ]);
            }

            /*
            * Primero dejamos el turno completamente cerrado.
            */
            $this->turnoRepository->updateTurno(
                $turno,
                [
                    'fecha_cierre' => now(),
                    'estado' => 'cerrado',

                    'total_ventas_combustible' =>
                        $totalVentasCombustibleSistema,

                    'total_ventas_combustible_sistema' =>
                        $totalVentasCombustibleSistema,

                    'total_ventas_combustible_fisica' =>
                        $totalVentasCombustibleFisica,

                    'diferencia_combustible' =>
                        $diferenciaCombustible,

                    'total_ventas_lubricantes' =>
                        $totalVentasLubricantes,

                    'total_creditos' =>
                        $totalCreditos,

                    'total_abonos' =>
                        $totalAbonos,

                    'pagos_efectivo' =>
                        $pagos['efectivo'],

                    'pagos_qr' =>
                        $pagos['qr'],

                    'pagos_datafono' =>
                        $pagos['datafono'],

                    'pagos_transferencia' =>
                        $pagos['transferencia'],

                    'pagos_consignacion' =>
                        $pagos['consignacion'],

                    'otros_movimientos' =>
                        $otrosMovimientos,

                    'otros_movimientos_detalle' =>
                        $dto->otros_movimientos_detalle,

                    'total_reportado' =>
                        $totalReportado,

                    'total_recaudo_esperado' =>
                        $totalRecaudoEsperado,

                    'total_sistema' =>
                        $totalRecaudoEsperado,

                    'balance_final' =>
                        $balanceFinal,

                    'observacion_cierre' =>
                        $dto->observacion_cierre,
                ]
            );

            /*
            * Los movimientos de caja SOLO se registran cuando
            * el turno ya quedó cerrado.
            */
            if ($turno->usuario->hasRole('islero')) {
                $this->registrarMovimientosCaja(
                    $dto,
                    $turno
                );

                $this->registrarMovimientosCajaAbonos(
                    $turno,
                    $dto->user_id
                );
            }

            return $this->findById($turno->id);
        });
    }

    private function registrarMovimientosCajaAbonos(
        TurnoIslero $turno,
        int $userId
    ): void {
        $abonos = $this->turnoRepository
            ->getAbonosCarteraByTurno($turno->id);

        foreach ($abonos as $abono) {

            $movimientoExistente = $this->turnoRepository
                ->findMovimientoCajaByOrigenAndOrigenId(
                    'cartera',
                    $abono->id,
                    'abono_cartera'
                );

            if ($movimientoExistente) {
                continue;
            }

            if (!$abono->caja_id) {
                throw new HttpException(
                    422,
                    "El abono de cartera #{$abono->id} no tiene una caja asociada."
                );
            }

            $this->turnoRepository->createMovimientoCaja([
                'caja_id' => $abono->caja_id,
                'tipo_movimiento' => 'ingreso',
                'categoria_movimiento' => 'abono_cartera',
                'origen_modulo' => 'cartera',
                'origen_id' => $abono->id,
                'medio_pago' => $abono->medio_pago,
                'monto' => $abono->valor,
                'descripcion' => 'Abono cartera #' . $abono->id,
                'user_id' => $userId,
                'fecha_movimiento' => now(),
            ]);
        }
    }

    private function descontarInventarioCombustible(
        TurnoIslero $turno,
        $lectura,
        float $galonesVendidos,
        int $userId
    ): void {

        if ($galonesVendidos <= 0) {
            return;
        }

        $bodegaId = $turno->usuario->bodega_id;

        if (!$bodegaId) {

            throw new HttpException(
                422,
                'El usuario no tiene una bodega asignada.'
            );

        }

        $productoId = $lectura->manguera->producto_id;

        $inventario = $this->ventaRepository
            ->findInventario(
                $productoId,
                $bodegaId
            );

        if (!$inventario) {

            throw new HttpException(
                422,
                "No existe inventario para el producto {$productoId} en la bodega del usuario."
            );

        }

        if ((float) $inventario->cantidad < $galonesVendidos) {

            throw new HttpException(
                422,
                "Inventario insuficiente para cerrar el turno."
            );

        }

        $this->ventaRepository->decrementInventario(
            $productoId,
            $bodegaId,
            $galonesVendidos
        );

        $this->ventaRepository->createMovimientoInventario([

            'tipo_movimiento' => 'venta_combustible',

            'producto_id' => $productoId,

            'bodega_origen_id' => $bodegaId,

            'bodega_destino_id' => null,

            'cantidad' => $galonesVendidos,

            'observacion' => "Salida automática por cierre del turno #{$turno->id}",

            'user_id' => $userId,

        ]);
    }


    public function resumenCierre(int $id): array
    {
        $turno = $this->findById($id);

        $ventasProductos = $this->turnoRepository
            ->getVentasLubricantesDetalleByTurno($turno->id);

        $abonosRecibidos = $this->turnoRepository
            ->getAbonosDetalleByTurno($turno->id);

        if (!in_array($turno->estado, ['abierto', 'pendiente_cierre'], true)) {
            throw new HttpException(
                422,
                'Solo se puede consultar el resumen de cierre para turnos abiertos o pendientes de cierre.'
            );
        }

        $totalVentasCombustible = $this->turnoRepository->sumVentasCombustibleByTurno($turno->id);
        $totalVentasLubricantes = $this->turnoRepository->sumVentasLubricantesByTurno($turno->id);
        $totalCreditos = $this->turnoRepository->sumVentasCreditoByTurno($turno->id);
        $totalAbonos = $this->turnoRepository->sumAbonosByTurno($turno->id);

        $ventasLubricantesDetalle = $this->turnoRepository
            ->getVentasLubricantesDetalleByTurno($turno->id);

        $abonosDetalle = $this->turnoRepository
            ->getAbonosDetalleByTurno($turno->id);

        $ventasProductos = $ventasLubricantesDetalle->map(function ($detalle) {
                return [
                    'id' => $detalle['id'],
                    'nombre' => $detalle['nombre'],
                    'cantidad' => (float) $detalle['cantidad'],
                    'precio_unitario' => (float) $detalle['precio_unitario'],
                    'total' => (float) $detalle['total'],
                ];
            })->values();

        $abonosRecibidos = $abonosDetalle
            ->map(function ($abono) {

                return [
                    'id' => $abono['id'],
                    'cliente' => $abono['cliente'],
                    'monto' => (float) $abono['monto'],
                    'fecha' => $abono['fecha']->toDateString()
                ];
            })->values();

        $resumen = $this->turnoRepository->getResumenDestinosTurno($turno->id);

        $destinos = $this->turnoRepository->getDestinosConCajaAbierta();

        $destinosRecaudo = [];

        foreach ($destinos as $destino) {

            $destinosRecaudo[$destino->id] = [

                'destino_recaudo_id' => $destino->id,

                'codigo' => $destino->codigo,

                'nombre' => $destino->nombre,

                'pagos' => [

                    'efectivo' => 0,

                    'qr' => 0,

                    'datafono' => 0,

                    'transferencia' => 0,

                    'consignacion' => 0,

                ],

                'total' => 0,

            ];
        }

        foreach ($resumen as $item) {

            if (!isset($destinosRecaudo[$item->destino_recaudo_id])) {
                continue;
            }

            $destinosRecaudo[$item->destino_recaudo_id]['pagos'][$item->metodo_pago] =
                (float) $item->total;

            $destinosRecaudo[$item->destino_recaudo_id]['total'] +=
                (float) $item->total;
        }

        $destinosRecaudo = array_values($destinosRecaudo);

        $totalReportadoSugerido = collect($destinosRecaudo)->sum('total');


        $totalSistema =
            $totalVentasCombustible +
            $totalVentasLubricantes -
            $totalCreditos;

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
            'ventas_productos' => $ventasProductos,
            'abonos_recibidos' => $abonosRecibidos,
            'destinos_recaudo' => $destinosRecaudo,
            'totales_pago_sugeridos' => [
                'creditos' => $totalCreditos,
                'total_reportado_sugerido' => $totalReportadoSugerido, 
            ],
            'total_por_destinos' => collect($destinosRecaudo)->sum('total'),
            'totales_sistema' => [
                'ventas_combustible' => $totalVentasCombustible,
                'ventas_lubricantes' => $totalVentasLubricantes,
                'creditos' => $totalCreditos,
                'abonos' => $totalAbonos,
                'total_sistema' => $totalSistema,
            ],

            'balance_preliminar' => $totalSistema - $totalReportadoSugerido,
            'nota' => 'La lectura sugerida se calcula con las ventas de combustible registradas por manguera. El islero debe confirmarla o corregirla con la lectura física real.',
        ];
    }

    public function manguerasDisponibles(int $estacionId): array
    {
        $mangueras = $this->turnoRepository->getManguerasDisponiblesByEstacion($estacionId);

        return $mangueras->map(function ($manguera) {
            $ultimaLectura = $this->turnoRepository->getUltimaLecturaFinalByManguera($manguera->id);

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


    private function registrarMovimientosCaja(
        CerrarTurnoIsleroDTO $dto,
        TurnoIslero $turno
    ): void {

        foreach ($dto->destinos_recaudo as $destino) {

            $destinoRecaudoId = (int) $destino['destino_recaudo_id'];

            foreach ($destino['pagos'] as $medioPago => $valor) {

                $valor = (float) ($valor ?? 0);

                if ($valor <= 0) {
                    continue;
                }

                /*
                * Evitar duplicar movimientos de caja para el mismo turno,
                * destino y medio de pago.
                */
                $movimientoExistente = $this->turnoRepository->findMovimientoCajaByOrigenMedioPagoAndDestino(
                        'turnos_islero',
                        $turno->id,
                        $medioPago,
                        $destinoRecaudoId
                    );

                if ($movimientoExistente) {
                    continue;
                }

                $tipoCaja = $this->resolverTipoCaja($medioPago);

                $caja = $this->ventaRepository
                    ->getCajaAbiertaByTipoAndDestino(
                        $tipoCaja,
                        $destinoRecaudoId
                    );

                if (!$caja) {
                    throw new HttpException(
                        422,
                        "No existe una caja abierta para el medio de pago {$medioPago} y el destino de recaudo {$destinoRecaudoId}."
                    );
                }

                $movimientoExistente = $this->turnoRepository
                    ->findMovimientoCajaByOrigenMedioPagoAndDestino(
                        'turnos_islero',
                        $turno->id,
                        $medioPago,
                        $destinoRecaudoId
                    );

                if ($movimientoExistente) {
                    continue;
                }

                $this->turnoRepository
                    ->createMovimientoCaja([
                        'caja_id' => $caja->id,
                        'tipo_movimiento' => 'ingreso',
                        'categoria_movimiento' => 'cierre_turno',
                        'origen_modulo' => 'turnos_islero',
                        'origen_id' => $turno->id,
                        'medio_pago' => $medioPago,
                        'monto' => $valor,
                        'descripcion' => 'Cierre turno islero #' . $turno->id,
                        'user_id' => $dto->user_id,
                        'fecha_movimiento' => now(),
                    ]);
            }
        }
    }

    private function validarRecaudoDestinos(
        TurnoIslero $turno,
        CerrarTurnoIsleroDTO $dto
    ): void {

        $destinos = $this->turnoRepository
            ->getDestinosConCajaAbierta();

        $esperado = [];

        foreach ($destinos as $destino) {

            $esperado[$destino->id] = [

                'codigo' => $destino->codigo,

                'nombre' => $destino->nombre,

                'efectivo' => 0,

                'qr' => 0,

                'datafono' => 0,

                'transferencia' => 0,

                'consignacion' => 0,

            ];
        }

        $resumenSistema = $this->turnoRepository
            ->getResumenDestinosTurno($turno->id);

        foreach ($resumenSistema as $item) {

            if (!isset($esperado[$item->destino_recaudo_id])) {
                continue;
            }

            $esperado[$item->destino_recaudo_id][$item->metodo_pago] =
                (float) $item->total;
        }

        $enviados = [];

        foreach ($dto->destinos_recaudo as $destino) {

            $destinoId = (int) $destino['destino_recaudo_id'];

            $enviados[] = $destinoId;

            if (!isset($esperado[$destinoId])) {

                throw new HttpException(
                    422,
                    "El destino de recaudo {$destinoId} no tiene una caja abierta."
                );
            }


            if ($esperado[$destinoId]['codigo'] === 'COMB') {
                continue;
            }

            foreach (
                [
                    'efectivo',
                    'qr',
                    'datafono',
                    'transferencia',
                    'consignacion',
                ] as $medio
            ) {

                $valorSistema =
                    (float) ($esperado[$destinoId][$medio] ?? 0);

                $valorEnviado =
                    (float) ($destino['pagos'][$medio] ?? 0);

                if (round($valorSistema, 2) !== round($valorEnviado, 2)) {

                    throw new HttpException(
                        422,
                        "El valor de {$medio} para {$esperado[$destinoId]['nombre']} no coincide con el sistema."
                    );
                }
            }
        }

        $faltantes = array_diff(
            array_keys($esperado),
            $enviados
        );

        if (!empty($faltantes)) {

            throw new HttpException(
                422,
                'Faltan destinos de recaudo por reportar.'
            );
        }
    }

    private function resolverTipoCaja(
        string $metodoPago
    ): string
    {
        return match ($metodoPago) {

            'efectivo' => 'efectivo',

            'qr',
            'transferencia',
            'datafono',
            'consignacion',
            'digital' => 'digital',

            default => throw new HttpException(
                422,
                "Método de pago {$metodoPago} inválido."
            ),
        };
    }

    public function solicitarCierre(
        SolicitarCierreTurnoIsleroDTO $dto
    ): TurnoIslero {
        return DB::transaction(function () use ($dto) {

            $turno = $this->turnoRepository->findById($dto->turno_id);

            if (!$turno) {
                throw new HttpException(
                    404,
                    'El turno no existe.'
                );
            }

            if ((int) $turno->user_id !== (int) $dto->user_id) {
                throw new HttpException(
                    403,
                    'El turno no pertenece al usuario autenticado.'
                );
            }

            if ($turno->estado !== 'abierto') {
                throw new HttpException(
                    422,
                    'El turno no puede ser enviado a cierre en su estado actual.'
                );
            }

            /*
            * Mangueras asignadas al turno.
            */
            $manguerasAsignadas = $turno->lecturas
                ->pluck('manguera_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            $manguerasEnviadas = collect($dto->lecturas_finales)
                ->pluck('manguera_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            /*
            * Validar que estén todas las mangueras.
            */
            $faltantes = array_diff(
                $manguerasAsignadas,
                $manguerasEnviadas
            );

            if (!empty($faltantes)) {
                throw new HttpException(
                    422,
                    'Debe enviar lectura final para todas las mangueras del turno.'
                );
            }

            /*
            * Validar que no lleguen mangueras ajenas al turno.
            */
            $extras = array_diff(
                $manguerasEnviadas,
                $manguerasAsignadas
            );

            if (!empty($extras)) {
                throw new HttpException(
                    422,
                    'Se enviaron mangueras que no pertenecen al turno.'
                );
            }

            /*
            * Guardar las lecturas finales.
            *
            * En este momento NO creamos AJT,
            * NO descontamos inventario y
            * NO registramos movimientos de caja.
            */
            foreach ($dto->lecturas_finales as $item) {

                $mangueraId = (int) $item['manguera_id'];

                $lectura = $this->turnoRepository
                    ->findLecturaByTurnoAndManguera(
                        $turno->id,
                        $mangueraId
                    );

                if (!$lectura) {
                    throw new HttpException(
                        422,
                        "La manguera {$mangueraId} no pertenece al turno."
                    );
                }

                $lecturaFinal = (float) $item['lectura_final'];
                $lecturaInicial = (float) $lectura->lectura_inicial;

                if ($lecturaFinal < $lecturaInicial) {
                    throw new HttpException(
                        422,
                        "La lectura final de la manguera {$mangueraId} no puede ser menor que la lectura inicial."
                    );
                }

                $galonesVendidos = round(
                    $lecturaFinal - $lecturaInicial,
                    3
                );

                /*
                * El precio pertenece a la lectura del turno.
                * No permitimos cambiarlo al solicitar cierre.
                */
                $precioGalon = round(
                    (float) $lectura->precio_galon,
                    2
                );

                $totalVentaFisica = round(
                    $galonesVendidos * $precioGalon,
                    2
                );

                $this->turnoRepository->updateLectura(
                    $lectura,
                    [
                        'lectura_final' => $lecturaFinal,
                        'galones_vendidos' => $galonesVendidos,
                        'total_venta' => $totalVentaFisica,
                    ]
                );
            }

            foreach ($dto->destinos_recaudo as $destino) {

                $pagos = $destino['pagos'] ?? [];

                $efectivo = round(
                    (float) ($pagos['efectivo'] ?? 0),
                    2
                );

                $qr = round(
                    (float) ($pagos['qr'] ?? 0),
                    2
                );

                $datafono = round(
                    (float) ($pagos['datafono'] ?? 0),
                    2
                );

                $transferencia = round(
                    (float) ($pagos['transferencia'] ?? 0),
                    2
                );

                $consignacion = round(
                    (float) ($pagos['consignacion'] ?? 0),
                    2
                );

                $total = round(
                    $efectivo
                    + $qr
                    + $datafono
                    + $transferencia
                    + $consignacion,
                    2
                );

                $turno->recaudos()->updateOrCreate(
                    [
                        'destino_recaudo_id' =>
                            (int) $destino['destino_recaudo_id'],
                    ],
                    [
                        'efectivo' => $efectivo,
                        'qr' => $qr,
                        'datafono' => $datafono,
                        'transferencia' => $transferencia,
                        'consignacion' => $consignacion,
                        'total' => $total,
                    ]
                );
            }

            $this->turnoRepository->updateTurno(
                $turno,
                [
                    'estado' => 'pendiente_cierre',

                    'otros_movimientos' => round(
                        (float) $dto->otros_movimientos,
                        2
                    ),

                    'otros_movimientos_detalle' =>
                        $dto->otros_movimientos_detalle,

                    'observacion_cierre' =>
                        $dto->observacion_cierre,
                ]
            );

            return $this->findById($turno->id)->load([
                'recaudos.destinoRecaudo',
            ]);
        });
    }

    public function revisionCierre(int $id): array
    {
        $turno = $this->turnoRepository->findById($id);

        if (!$turno) {
            throw new HttpException(
                404,
                'Turno no encontrado.'
            );
        }

        if ($turno->estado !== 'pendiente_cierre') {
            throw new HttpException(
                422,
                'El turno no se encuentra pendiente de revisión.'
            );
        }

        /*
        * Cargar toda la información actual del turno.
        */
        $turno->load([
            'estacion',
            'usuario',
            'lecturas.manguera.producto',
            'recaudos.destinoRecaudo',
        ]);

        /*
        * Recalcular información actual del turno.
        *
        * Estos valores se obtienen directamente de las ventas
        * y abonos actuales, ya que el turno puede haber sido
        * modificado mientras permanece pendiente de cierre.
        */
        $totalVentasCombustibleSistema =
            $this->turnoRepository
                ->sumVentasCombustibleByTurno($turno->id);

        $totalVentasLubricantes =
            $this->turnoRepository
                ->sumVentasLubricantesByTurno($turno->id);

        $totalCreditos =
            $this->turnoRepository
                ->sumVentasCreditoByTurno($turno->id);

        $totalAbonos =
            $this->turnoRepository
                ->sumAbonosByTurno($turno->id);

        /*
        * Combustible físico según las lecturas actualmente guardadas.
        */
        $totalVentasCombustibleFisica = round(
            $turno->lecturas->sum(function ($lectura) {
                return (float) $lectura->total_venta;
            }),
            2
        );

        $diferenciaCombustible = round(
            $totalVentasCombustibleFisica
            - (float) $totalVentasCombustibleSistema,
            2
        );

        /*
        * Resumen de pagos por destino.
        */
        $resumenDestinos = $this->turnoRepository
            ->getResumenDestinosTurno($turno->id);

        /*
        * Destinos que actualmente tienen caja abierta.
        */
        $destinosCaja = $this->turnoRepository
            ->getDestinosConCajaAbierta();

        $destinos = [];

        foreach ($destinosCaja as $destino) {
            $destinos[$destino->id] = [
                'destino_recaudo_id' => (int) $destino->id,
                'codigo' => $destino->codigo,
                'nombre' => $destino->nombre,
                'pagos' => [
                    'efectivo' => 0,
                    'qr' => 0,
                    'datafono' => 0,
                    'transferencia' => 0,
                    'consignacion' => 0,
                ],
            ];
        }

        foreach ($resumenDestinos as $item) {
            if (!isset($destinos[$item->destino_recaudo_id])) {
                continue;
            }

            $metodo = $item->metodo_pago;

            if (
                array_key_exists(
                    $metodo,
                    $destinos[$item->destino_recaudo_id]['pagos']
                )
            ) {
                $destinos[$item->destino_recaudo_id]['pagos'][$metodo] =
                    round((float) $item->total, 2);
            }
        }

        /*
        * Información de ventas de lubricantes.
        */
        $lubricantes = $this->turnoRepository
            ->getVentasLubricantesDetalleByTurno($turno->id);

        /*
        * Información de abonos.
        */
        $abonos = $this->turnoRepository
            ->getAbonosDetalleByTurno($turno->id);

        /*
        * Devolver información actual para la revisión administrativa.
        */
        return [
            'turno' => $turno,

            'resumen' => [
                'total_ventas_combustible_sistema' =>
                    round((float) $totalVentasCombustibleSistema, 2),

                'total_ventas_combustible_fisica' =>
                    $totalVentasCombustibleFisica,

                'diferencia_combustible' =>
                    $diferenciaCombustible,

                'total_ventas_lubricantes' =>
                    round((float) $totalVentasLubricantes, 2),

                'total_creditos' =>
                    round((float) $totalCreditos, 2),

                'total_abonos' =>
                    round((float) $totalAbonos, 2),

                'otros_movimientos' =>
                    round((float) $turno->otros_movimientos, 2),

                'otros_movimientos_detalle' =>
                    $turno->otros_movimientos_detalle,

                'observacion_cierre' =>
                    $turno->observacion_cierre,
            ],

            'destinos_recaudo' =>
                array_values($destinos),

            'lubricantes' =>
                $lubricantes,

            'abonos' =>
                $abonos,
        ];
    }

    

    
}