<?php

namespace App\Services;

use App\Models\Sale;

/**
 * Cálculos del ticket de venta, en un solo lugar: los usan el ticket HTML,
 * el PDF y PrinterNinja, así lo impreso nunca difiere de lo que muestra el sistema.
 */
class SaleTicketService
{
    /** Productos de la venta con precio final, descuentos y cantidad real (kg con 3 decimales). */
    public static function products(Sale $sale)
    {
        return $sale->details->map(function ($item) {
            $basePrice = $item->manual_price ?? $item->price;
            $finalPrice = $basePrice;
            $unitDiscount = 0;

            if (!is_null($item->manual_discount) && $item->manual_discount > 0) {
                $unitDiscount = $item->manual_discount;
                $finalPrice = $basePrice - $unitDiscount;
            } elseif (!is_null($item->manual_discount_percent) && $item->manual_discount_percent > 0) {
                $unitDiscount = $basePrice * ($item->manual_discount_percent / 100);
                $finalPrice = $basePrice - $unitDiscount;
            }

            $isWeighted = (bool) ($item->product->is_weighted ?? false);
            $quantity = (float) $item->quantity;

            if ($isWeighted && $finalPrice > 0 && !is_null($item->sub_total)) {
                $quantity = round(((float) $item->sub_total) / $finalPrice, 3);
            }

            return [
                'name'           => $item->product->name,
                'quantity'       => $quantity,
                'is_weighted'    => $isWeighted,
                'base_price'     => round($basePrice, 0),
                'final_price'    => round($finalPrice, 0),
                'subtotal_base'  => round($basePrice * $quantity, 0),
                'subtotal'       => round($finalPrice * $quantity, 0),
                'unit_discount'  => round($unitDiscount, 0),
                'total_discount' => round($unitDiscount * $quantity, 0),
            ];
        });
    }

    /** Totales, pago, saldo y vuelto de la venta. */
    public static function totals(Sale $sale, $products)
    {
        $totalWithoutDiscount = $products->sum('subtotal_base');
        $totalSale = round($sale->total, 0);

        $pendingBalance = $sale->payment_type === 'CREDITO'
            ? max(0, $totalSale - $sale->cash)
            : 0;

        $change = ($sale->payment_type === 'CREDITO' || $sale->change < 0)
            ? 0
            : round($sale->change, 0);

        return [
            // Cantidad de productos: sumar cantidades mezclaba kilos y unidades (0,75 kg + 1 = 1,75)
            'total_items'            => $products->count(),
            'total'                  => $totalSale,
            'total_without_discount' => $totalWithoutDiscount,
            'total_savings'          => $totalWithoutDiscount - $totalSale,
            'cash'                   => round($sale->cash, 0),
            'change'                 => $change,
            'pending_balance'        => $pendingBalance,
        ];
    }
}
