<?php

namespace App\Modules\Dashboard\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarDashboardRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'widgets' => [

                'required',

                'array',

                'min:1'

            ],

            'widgets.*.widget_id' => [

                'required',

                'integer',

                'exists:dashboard_widgets,id'

            ],

            'widgets.*.visible' => [

                'required',

                'boolean'

            ],

            'widgets.*.orden' => [

                'required',

                'integer',

                'min:1'

            ],

        ];
    }
}