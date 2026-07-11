<?php

namespace App\Modules\TurnosIslero\Application\DTOs;

class CerrarTurnoIsleroDTO
{
    public function __construct(
        public int $turno_id,
        public int $user_id,

        /**
         * [
         *   [
         *      "manguera_id"=>1,
         *      "lectura_final"=>1050
         *   ]
         * ]
         */
        public array $lecturas_finales,

        /**
         * [
         *   [
         *      "destino_recaudo_id"=>1,
         *      "pagos"=>[
         *          "efectivo"=>100000,
         *          "qr"=>0,
         *          "datafono"=>0,
         *          "transferencia"=>0,
         *          "consignacion"=>0
         *      ]
         *   ]
         * ]
         */
        public array $destinos_recaudo,

        public float $otros_movimientos,
        public ?string $otros_movimientos_detalle,

        public ?string $observacion_cierre,
    ) {}
}