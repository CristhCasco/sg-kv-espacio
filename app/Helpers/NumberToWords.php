<?php

namespace App\Helpers;

class NumberToWords
{
    public static function convert($number)
    {
        $number = round($number); // Redondear si hay decimales

        $formatter = new \NumberFormatter("es_PY", \NumberFormatter::SPELLOUT);
        $words = $formatter->format($number);

        // Corrección de tildes y plurales
        $words = str_replace("millon", "millón", $words);
        $words = str_replace("millons", "millones", $words);

        // Normalización visual
        $words = preg_replace('/\s+/', ' ', trim($words));

        return ucfirst($words);
    }
}
