<?php

namespace App\Modules\Caja\Application\DTOs;

class CierreCajaDTO
{
    /**
     * @param CierreCajaItemDTO[] $cierres
     */
    public function __construct(
        public array $cierres,
        public ?string $observacion_cierre,
        public int $user_id,
    ) {}
}