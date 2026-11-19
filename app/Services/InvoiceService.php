<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Sale;

/**
 * Datos de la factura legal, en un solo lugar: los usan la vista del navegador y PrinterNinja.
 */
class InvoiceService
{
    /**
     * Venta con lo que imprime la factura y la empresa emisora.
     * $summary: "Factura sin detalle", con una sola línea "CONSUMISIÓN" por el total.
     */
    public static function sale($saleId, $summary = false)
    {
        $relations = [
            'customer:id,person,company,name,last_name,ci,document_number,dv,address,phone,tax_condition_id',
            'user:id,name',
            'company',
        ];
        if (! $summary) {
            $relations[] = 'details:id,sale_id,product_id,quantity,price,sub_total';
            $relations[] = 'details.product:id,name,tax_rate';
        }

        $sale = Sale::with($relations)->findOrFail($saleId);

        if ($summary) {
            // 🔹 Reemplaza detalles por una sola línea "CONSUMISIÓN"
            $sale->details = collect([
                (object)[
                    'quantity' => 1,
                    'product' => (object)['name' => 'CONSUMISIÓN'],
                    'price' => $sale->total,
                    'sub_total' => $sale->total,
                ]
            ]);
        }

        return [$sale, $sale->company ?? Company::first()];
    }

    /**
     * Pie de la factura en el formato habitual: subtotales por tasa con IVA incluido
     * (exentas + 5% + 10% = total a pagar) y la liquidación del IVA aparte.
     * Sale de lo guardado al registrar la venta (base gravada + IVA de cada tasa).
     */
    public static function totals(Sale $sale)
    {
        $iva5 = (int) round($sale->total_iva5 ?? 0, 0);
        $iva10 = (int) round($sale->total_iva10 ?? 0, 0);

        return [
            'exentas'   => (int) round($sale->total_exentas ?? 0, 0),
            'sub5'      => (int) round($sale->total_gravadas_5 ?? 0, 0) + $iva5,
            'sub10'     => (int) round($sale->total_gravadas_10 ?? 0, 0) + $iva10,
            'iva5'      => $iva5,
            'iva10'     => $iva10,
            'total_iva' => $iva5 + $iva10,
            'total'     => $sale->total,
        ];
    }
}
