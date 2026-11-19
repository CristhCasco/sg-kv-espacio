<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;

/**
 * Lee las etiquetas que imprime la balanza según Configuración → Balanza.
 *
 *   Peso:   2 | 000001 | 00550 | 7  → producto "1", 550 g = 0,550 kg
 *   Unidad: 1 | 000002 | 03000 | 4  → producto "2", 3 unidades
 *           │     │        │     └─ dígito de control EAN-13 (se ignora)
 *           │     │        └─────── peso o cantidad
 *           │     └──────────────── código del producto (PLU)
 *           └────────────────────── prefijo: indica si es peso o unidad
 */
class ScaleBarcodeService
{
    const FIELDS = [
        'scale_enabled', 'scale_barcode_length', 'scale_weight_prefix', 'scale_unit_prefix',
        'scale_code_start', 'scale_code_length', 'scale_value_start', 'scale_value_length',
        'scale_weight_decimals', 'scale_unit_decimals',
    ];

    const DEFAULTS = [
        'scale_enabled'         => true,
        'scale_barcode_length'  => 13,
        'scale_weight_prefix'   => '2',
        'scale_unit_prefix'     => '1',
        'scale_code_start'      => 2,
        'scale_code_length'     => 6,
        'scale_value_start'     => 8,
        'scale_value_length'    => 5,
        'scale_weight_decimals' => 3,
        'scale_unit_decimals'   => 3,
    ];

    private $config;

    /**
     * @param array|null $config Configuración a usar; por defecto la guardada en la empresa.
     */
    public function __construct(array $config = null)
    {
        if ($config === null) {
            $company = Company::current();
            $config = $company ? $company->only(self::FIELDS) : [];
        }

        $this->config = array_merge(self::DEFAULTS, array_filter($config, function ($v) {
            return $v !== null;
        }));
    }

    /**
     * Interpreta una etiqueta de balanza.
     *
     * Devuelve null si el código no tiene formato de etiqueta (no es de la balanza).
     * Si tiene formato de etiqueta devuelve:
     *   ['type' => 'weight'|'unit', 'code', 'raw', 'value', 'product' => Product|null, 'error' => string|null]
     * 'value' es el peso en kg o la cantidad de unidades.
     */
    public function parse($barcode)
    {
        $barcode = trim((string) $barcode);
        $c = $this->config;

        if (! $c['scale_enabled'] || ! ctype_digit($barcode) || strlen($barcode) !== (int) $c['scale_barcode_length']) {
            return null;
        }

        $type = $this->detectType($barcode);
        if (! $type) {
            return null;
        }

        $code = (string) substr($barcode, (int) $c['scale_code_start'] - 1, (int) $c['scale_code_length']);
        $raw  = (string) substr($barcode, (int) $c['scale_value_start'] - 1, (int) $c['scale_value_length']);

        if ($code === '' || $raw === '' || ! ctype_digit($raw)) {
            return null;
        }

        $decimals = (int) ($type === 'weight' ? $c['scale_weight_decimals'] : $c['scale_unit_decimals']);
        $value = round((int) $raw / pow(10, $decimals), 3);

        $result = [
            'type'    => $type,
            'code'    => $code,
            'raw'     => $raw,
            'value'   => $value,
            'product' => $this->findProduct($code),
            'error'   => null,
        ];

        $result['error'] = $this->validate($result);

        return $result;
    }

    private function detectType($barcode)
    {
        $weightPrefix = trim((string) $this->config['scale_weight_prefix']);
        $unitPrefix = trim((string) $this->config['scale_unit_prefix']);

        // Si un prefijo contiene al otro (ej: "2" y "21"), se prueba primero el más largo
        $prefixes = array_filter(['weight' => $weightPrefix, 'unit' => $unitPrefix], 'strlen');
        uasort($prefixes, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($prefixes as $type => $prefix) {
            if (strpos($barcode, $prefix) === 0) {
                return $type;
            }
        }

        return null;
    }

    private function validate(array $r)
    {
        $product = $r['product'];

        if (! $product) {
            return "Etiqueta de balanza: no hay ningún producto con el código {$r['code']}.";
        }

        if ($r['type'] === 'weight') {
            if (! $product->is_weighted) {
                return "La etiqueta es de peso, pero \"{$product->name}\" está configurado por unidad.";
            }
            if ($r['value'] <= 0) {
                return "La etiqueta de \"{$product->name}\" no tiene un peso válido.";
            }
        } else {
            if ($product->is_weighted) {
                return "La etiqueta es por unidad, pero \"{$product->name}\" está configurado por peso.";
            }
            if ($r['value'] <= 0 || floor($r['value']) != $r['value']) {
                return "La etiqueta de \"{$product->name}\" no tiene una cantidad entera válida ({$r['value']}).";
            }
        }

        return null;
    }

    /** Busca por el código sin ceros a la izquierda (000123 → 123) y, si no existe, tal cual. */
    private function findProduct($code)
    {
        $trimmed = ltrim($code, '0');

        return ($trimmed !== '' ? Product::where('barcode', $trimmed)->first() : null)
            ?? Product::where('barcode', $code)->first();
    }
}
