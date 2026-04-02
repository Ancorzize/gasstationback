<?php

namespace App\Modules\Perfil\Application\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PerfilService
{
    public function update(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                throw new HttpException(422, 'La contraseña actual es incorrecta.');
            }

            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return $user->fresh();
    }
}