<?php

namespace App\Soporte;

class ImporteReporte
{
    // No limitar acumulados al máximo de un importe individual ni perder signos.
    public static function decimal(int $centavos): string
    {
        return ($centavos < 0 ? '-' : '').Importes::aDecimal(abs($centavos));
    }
}
