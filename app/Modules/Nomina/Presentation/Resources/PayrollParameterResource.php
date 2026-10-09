<?php

namespace App\Modules\Nomina\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollParameterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => (int) $this->year,
            'smlmv' => (float) $this->smlmv,
            'transport_assistance' => (float) $this->transport_assistance,
            'max_smlmv_for_transport' => (float) $this->max_smlmv_for_transport,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
