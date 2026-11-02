<?php

namespace App\Soporte;

use InvalidArgumentException;

class Importe
{
    public static function centavos(string $valor): int
    {
        if (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $valor)) {
            throw new InvalidArgumentException('Importe monetario inválido.');
        }
        $partes = explode('.', $valor);

        return (int) $partes[0] * 100 + (int) str_pad($partes[1] ?? '', 2, '0');
    }

    public static function decimal(int $centavos): string
    {
        if ($centavos < 0) {
            return '-'.self::decimal(-$centavos);
        }

        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
