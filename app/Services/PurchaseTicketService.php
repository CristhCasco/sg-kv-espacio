<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Purchase;
use Carbon\Carbon;

/**
 * Cálculos del ticket de compra, en un solo lugar: los usan el PDF y PrinterNinja.
 */
class PurchaseTicketService
{
    /** Datos del comprobante de compra (los mismos que recibe la vista del PDF). */
    public static function data(Purchase $compra, Company $company)
    {
        $productos = $compra->details->map(function ($item) {
            $precioBase = $item->manual_price ?? $item->price;
            $precioFinal = $precioBase;
            $descuentoUnitario = 0;

            if (!is_null($item->manual_discount)) {
                $descuentoUnitario = $item->manual_discount;
                $precioFinal = $precioBase - $descuentoUnitario;
            } elseif (!is_null($item->manual_discount_percent)) {
                $descuentoUnitario = $precioBase * ($item->manual_discount_percent / 100);
                $precioFinal = $precioBase - $descuentoUnitario;
            }

            return [
                'nombre'           => $item->product->name,
                'cantidad'         => $item->quantity,
                'precio_base'      => round($precioBase, 2),
                'precio_final'     => round($precioFinal, 2),
                'subtotal_base'    => round($precioBase * $item->quantity, 2),
                'subtotal'         => round($precioFinal * $item->quantity, 2),
                'descuento_unit'   => round($descuentoUnitario, 2),
                'descuento_total'  => round($descuentoUnitario * $item->quantity, 2),
            ];
        });

        $totalConDescuento = $productos->sum('subtotal');
        $saldoPendiente = ($compra->payment_type === 'CREDITO')
            ? max(0, $totalConDescuento - $compra->cash)
            : 0;

        return [
            'fecha'              => Carbon::parse($compra->created_at)->format('d/m/Y H:i'),
            'empresa'            => [
                'name'    => $company->name,
                'ruc'     => $company->ruc,
                'dv'      => $company->dv,
                'phone'   => $company->phone ?? '',
                'address' => $company->address ?? '',
            ],
            'company'            => $company,
            'proveedor'          => $compra->supplier->display_name ?? 'Sin proveedor',
            'compra_id'          => $compra->id,
            'tipo_pago'          => $compra->payment_type,
            'productos'          => $productos,
            'total_items'        => $productos->count(),
            'total'              => $totalConDescuento,
            'total_sin_descuento'=> $productos->sum('subtotal_base'),
            'ahorro_total'       => $productos->sum('descuento_total'),
            'efectivo'           => $compra->cash,
            'vuelto'             => ($compra->payment_type === 'CREDITO' || $compra->change < 0) ? 0 : $compra->change,
            'saldo_pendiente'    => $saldoPendiente,
            'usuario'            => $compra->user->name,
        ];
    }
}
