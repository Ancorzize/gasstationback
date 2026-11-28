<?php

namespace App\Modules\Auth\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Auth\Application\Services\AuthService;
use App\Modules\Auth\Infrastructure\Mappers\AuthMapper;
use App\Modules\Auth\Presentation\Requests\LoginRequest;
use App\Modules\Auth\Presentation\Resources\AuthUserResource;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request)
    {
        try {
            /*if (!$request->user()->hasRole('admin')) {
                return ApiResponse::error('No autorizado', 403);
            }

            if (!$request->user()->can('crear_clientes')) {
                return ApiResponse::error('Sin permisos', 403);
            }*/

            $dto = AuthMapper::fromArrayToLoginDTO($request->validated());
            $result = $this->authService->login($dto);

            return ApiResponse::success([
                'token' => $result['token'],
                'user' => new AuthUserResource($result['user']),
                'rol' => $result['rol']
            ], 'Inicio de sesión exitoso.');
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function loginWithQr(Request $request)
    {
        try {
            $validated = $request->validate([
                'qr_token' => ['required', 'string', 'max:128'],
            ], [
                'qr_token.required' => 'El código QR es obligatorio.',
            ]);

            $result = $this->authService->loginWithQr($validated['qr_token']);

            return ApiResponse::success([
                'token' => $result['token'],
                'user' => new AuthUserResource($result['user']),
                'rol' => $result['rol'],
            ], 'Inicio de sesión con QR exitoso.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error($e->validator->errors()->first(), 422);
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user());

            return ApiResponse::success(null, 'Sesión cerrada correctamente.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function me(Request $request)
    {
        try {
            return ApiResponse::success(
                new AuthUserResource($this->authService->me($request->user())),
                'Usuario autenticado.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}