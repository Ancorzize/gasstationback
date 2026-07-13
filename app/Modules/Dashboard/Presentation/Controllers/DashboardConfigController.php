<?php

namespace App\Modules\Dashboard\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Shared\Responses\ApiResponse;
use App\Modules\Dashboard\Application\Services\DashboardConfigService;
use App\Modules\Dashboard\Infrastructure\Mappers\DashboardConfigMapper;
use App\Modules\Dashboard\Presentation\Requests\GuardarDashboardRolRequest;
use App\Modules\Dashboard\Presentation\Resources\DashboardWidgetRoleResource;

class DashboardConfigController extends Controller
{
    public function __construct(
        protected DashboardConfigService $service
    ) {}

    public function roles(Request $request)
    {
        try {

           /* if (!$request->user()->can('ver_roles')) {

                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );

            }*/

            return ApiResponse::success(

                $this->service->getRoles(),

                'Roles.'

            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno.',
                500
            );

        }
    }

    public function configuracion(
        Request $request,
        int $roleId
    )
    {
        try {

            /*if (!$request->user()->can('configurar_dashboard')) {

                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );

            }*/

            $configuracion =
                $this->service
                    ->getConfiguracionRol(
                        $roleId
                    );

            return ApiResponse::success([

                'role' => $configuracion['role'],

                'widgets' => DashboardWidgetRoleResource::collection(
                    $configuracion['widgets']
                )

            ]);

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno.',
                500
            );

        }
    }

    public function guardar(
        GuardarDashboardRolRequest $request,
        int $roleId
    )
    {
        try {

            /*if (!$request->user()->can('configurar_dashboard')) {

                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );

            }*/

            $dto =
                DashboardConfigMapper::fromArray(
                    $request->validated(),
                    $roleId
                );

            $this->service
                ->guardarConfiguracion(
                    $dto
                );

            return ApiResponse::success(

                true,

                'Configuración guardada correctamente.'

            );

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno.',
                500
            );

        }
    }
    
}