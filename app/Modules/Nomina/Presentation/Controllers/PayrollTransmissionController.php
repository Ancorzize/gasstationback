<?php

namespace App\Modules\Nomina\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use App\Modules\Nomina\Application\Services\PayrollTransmissionService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Controlador HTTP para orquestar la transmisión de Nómina Electrónica hacia MATIAS API.
 */
class PayrollTransmissionController extends Controller
{
    public function __construct(
        protected PayrollTransmissionService $transmissionService
    ) {}

    /**
     * Transmitir electrónicamente una liquidación de nómina a MATIAS API (POST /ep/payroll).
     *
     * @param Request $request
     * @param int $id ID de la liquidación de nómina (settlementId).
     */
    public function transmitir(Request $request, int $id)
    {
        try {
            $options = $request->only([
                'resolution_number',
                'prefix',
                'prefijo',
                'document_number',
                'issue_date',
                'force_status',
                'notes',
            ]);

            $resultado = $this->transmissionService->transmitir($id, $options);

            if (!$resultado['exitoso']) {
                return ApiResponse::error(
                    $resultado['mensaje'] ?? 'La nómina fue rechazada por la API de MATIAS.',
                    422,
                    $resultado
                );
            }

            return ApiResponse::success(
                $resultado,
                'Nómina electrónica transmitida exitosamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Error interno al transmitir la nómina electrónica: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Reemplazar una nómina electrónica previamente transmitida a MATIAS API (POST /ep/payroll/replace).
     *
     * @param Request $request
     * @param int $id ID de la liquidación de nómina (settlementId).
     */
    public function reemplazar(Request $request, int $id)
    {
        try {
            $options = $request->only([
                'resolution_number',
                'prefix',
                'prefijo',
                'document_number',
                'issue_date',
                'generation_date',
                'force_status',
                'notes',
            ]);

            $resultado = $this->transmissionService->reemplazar($id, $options);

            if (!$resultado['exitoso']) {
                return ApiResponse::error(
                    $resultado['mensaje'] ?? 'El reemplazo de la nómina fue rechazado por MATIAS API.',
                    422,
                    $resultado
                );
            }

            return ApiResponse::success(
                $resultado,
                'Reemplazo de nómina electrónica procesado exitosamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Error interno al reemplazar la nómina electrónica: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Solicitar eliminación/anulación de una nómina electrónica previamente transmitida a MATIAS API (POST /ep/payroll/delete).
     *
     * @param Request $request
     * @param int $id ID de la liquidación de nómina (settlementId).
     */
    public function eliminar(Request $request, int $id)
    {
        try {
            $options = $request->only([
                'resolution_number',
                'prefix',
                'prefijo',
                'document_number',
                'generation_date',
                'force_status',
                'notes',
            ]);

            $resultado = $this->transmissionService->eliminar($id, $options);

            if (!$resultado['exitoso']) {
                return ApiResponse::error(
                    $resultado['mensaje'] ?? 'La eliminación de la nómina fue rechazada por MATIAS API.',
                    422,
                    $resultado
                );
            }

            return ApiResponse::success(
                $resultado,
                'Eliminación de nómina electrónica procesada exitosamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Error interno al eliminar la nómina electrónica: ' . $e->getMessage(),
                500
            );
        }
    }
}
