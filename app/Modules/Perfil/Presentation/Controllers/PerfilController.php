<?php

namespace App\Modules\Perfil\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\Request;
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

    public function qr(Request $request)
    {
        try {
            $user = $request->user();
            $qrToken = $user->ensureQrLoginToken();

            return ApiResponse::success([
                'qr_token' => $qrToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol' => $user->getRoleNames()->first(),
                ],
            ], 'Código QR obtenido correctamente.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error al obtener el código QR.', 500);
        }
    }

    public function regenerateQr(Request $request)
    {
        try {
            $user = $request->user();
            $qrToken = $user->regenerateQrLoginToken();

            return ApiResponse::success([
                'qr_token' => $qrToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol' => $user->getRoleNames()->first(),
                ],
            ], 'Código QR regenerado correctamente. El código anterior ya no es válido.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error al regenerar el código QR.', 500);
        }
    }
}