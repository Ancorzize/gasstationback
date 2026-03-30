<?php

namespace App\Shared\Responses;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Operación exitosa',
        int $code = 200
    ) {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error(
        string $message = 'Ha ocurrido un error',
        int $code = 400,
        mixed $errors = null
    ) {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}