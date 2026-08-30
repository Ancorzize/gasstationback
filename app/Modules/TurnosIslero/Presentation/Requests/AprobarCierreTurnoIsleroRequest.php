<?php

namespace App\Modules\TurnosIslero\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AprobarCierreTurnoIsleroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}