<?php

namespace App\Soporte;

use InvalidArgumentException;

class Importes
{
    public static function aCentavos(string $importe): int
    {
        if (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $importe)) {
            throw new InvalidArgumentException('Importe monetario inválido.');
        }
        [$entero, $decimal] = array_pad(explode('.', $importe, 2), 2, '');

        return (int) $entero * 100 + (int) str_pad($decimal, 2, '0');
    }

    public static function aDecimal(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
