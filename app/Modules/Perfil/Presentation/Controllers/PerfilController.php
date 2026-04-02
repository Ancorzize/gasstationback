<?php

namespace App\Modules\Perfil\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Perfil\Application\Services\PerfilService;
use App\Modules\Perfil\Presentation\Requests\UpdatePerfilRequest;
use App\Modules\Perfil\Presentation\Resources\PerfilResource;

class PerfilController extends Controller
{
    public function __construct(
        protected PerfilService $perfilService
    ) {}

    public function update(UpdatePerfilRequest $request)
    {
        try {
            $user = $this->perfilService->update(
                $request->user(),
                $request->validated()
            );

            return ApiResponse::success(
                new PerfilResource($user),
                'Perfil actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}