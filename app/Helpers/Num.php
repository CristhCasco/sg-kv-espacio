<?php

namespace App\Helpers;

/**
 * Formato de números para mostrar al cliente, sin decimales que confundan.
 * Las cantidades se guardan como decimal(…,3): "1.000" se leía como mil.
 */
class Num
{
    // Cantidad o stock: entero sin decimales; por peso, solo los decimales que tenga (1,5 kg).
    // Sin separador de miles: los montos usan "30,000" y "1.000" junto a ellos se leía como 1
    public static function qty($value)
    {
        $n = round((float) $value, 3);

        if (floor($n) == $n) {
            return number_format($n, 0, ',', '');
        }

        return rtrim(rtrim(number_format($n, 3, ',', ''), '0'), ',');
    }

    // Porcentaje: como mucho un decimal (12,5 %)
    public static function pct($value)
    {
        $n = round((float) $value, 1);

        return floor($n) == $n
            ? number_format($n, 0, ',', '.')
            : number_format($n, 1, ',', '.');
    }

    // Para cargar un valor en un campo de formulario: 30000.00 -> 30000, 2.500 -> 2.5
    public static function input($value)
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $value;
        }

        $n = round((float) $value, 3);

        return floor($n) == $n ? (int) $n : $n;
    }
}
