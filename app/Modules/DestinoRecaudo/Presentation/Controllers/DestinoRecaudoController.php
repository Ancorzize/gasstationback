<?php

namespace App\Modules\DestinoRecaudo\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\DestinoRecaudo\Application\Services\DestinoRecaudoService;
use App\Modules\DestinoRecaudo\Infrastructure\Mappers\DestinoRecaudoMapper;
use App\Modules\DestinoRecaudo\Presentation\Requests\CreateDestinoRecaudoRequest;
use App\Modules\DestinoRecaudo\Presentation\Requests\UpdateDestinoRecaudoRequest;
use App\Modules\DestinoRecaudo\Presentation\Resources\DestinoRecaudoResource;

class DestinoRecaudoController extends Controller
{
    public function __construct(
        protected DestinoRecaudoService $service
    ) {}

    public function index(Request $request)
    {
        try {
            
            if (!$request->user()->can('ver_destinos_recaudo')) {
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $items = $this->service->paginate(
                [
                    'search'=>$request->search,
                    'is_active'=>$request->is_active,
                ],
                (int)$request->get('per_page',10)
            );

            return ApiResponse::success([

                'items'=>DestinoRecaudoResource::collection(
                    $items->items()
                ),

                'pagination'=>[
                    'current_page'=>$items->currentPage(),
                    'last_page'=>$items->lastPage(),
                    'per_page'=>$items->perPage(),
                    'total'=>$items->total(),
                ]

            ]);

        } catch (\Throwable $e){

            return ApiResponse::error(
                'Error interno del servidor o sin permisos.',
                500
            );

        }
    }

    public function show(int $id,Request $request)
    {
        try{

            if(!$request->user()->can('ver_destinos_recaudo')){
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            return ApiResponse::success(

                new DestinoRecaudoResource(

                    $this->service->findById($id)

                )

            );

        }catch(HttpException $e){

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        }catch(\Throwable){

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );

        }
    }

    public function store(CreateDestinoRecaudoRequest $request)
    {
        try{

            if(!$request->user()->can('crear_destino_recaudo')){
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $dto=DestinoRecaudoMapper::fromArrayToCreateDTO(
                $request->validated()
            );

            return ApiResponse::success(

                new DestinoRecaudoResource(

                    $this->service->create($dto)

                ),

                'Destino creado correctamente.',

                201

            );

        }catch(HttpException $e){

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        }catch(\Throwable){

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );

        }
    }

    public function update(
        int $id,
        UpdateDestinoRecaudoRequest $request
    )
    {
        try{

            if(!$request->user()->can('editar_destino_recaudo')){
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $dto=DestinoRecaudoMapper::fromArrayToUpdateDTO(
                $request->validated()
            );

            return ApiResponse::success(

                new DestinoRecaudoResource(

                    $this->service->update(
                        $id,
                        $dto
                    )

                ),

                'Destino actualizado correctamente.'

            );

        }catch(HttpException $e){

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        }catch(\Throwable){

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );

        }
    }

    public function changeStatus(
        int $id,
        Request $request
    )
    {
        try{

            if(!$request->user()->can('editar_destino_recaudo')){
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            return ApiResponse::success(

                new DestinoRecaudoResource(

                    $this->service->changeStatus(
                        $id,
                        filter_var(
                            $request->is_active,
                            FILTER_VALIDATE_BOOLEAN
                        )
                    )

                )

            );

        }catch(HttpException $e){

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        }catch(\Throwable){

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );

        }
    }

    public function destroy(
        int $id,
        Request $request
    )
    {
        try{

            if(!$request->user()->can('eliminar_destino_recaudo')){
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $this->service->delete($id);

            return ApiResponse::success(
                null,
                'Destino eliminado correctamente.'
            );

        }catch(HttpException $e){

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        }catch(\Throwable){

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );

        }
    }
}