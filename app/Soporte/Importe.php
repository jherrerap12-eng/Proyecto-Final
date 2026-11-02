<?php

namespace App\Soporte;

class Importe
{
    public static function centavos(string $valor): int
    {
        return Importes::aCentavos($valor);
    }

    public static function decimal(int $centavos): string
    {
        if ($centavos < 0) {
            return '-'.self::decimal(-$centavos);
        }

        return Importes::aDecimal($centavos);
    }
}
