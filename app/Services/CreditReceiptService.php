<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SaleCreditPayment;
use Carbon\Carbon;

/**
 * Cálculos del comprobante de pago de crédito, en un solo lugar: los usan el PDF y PrinterNinja.
 */
class CreditReceiptService
{
    /** Datos del comprobante (los mismos que recibe la vista del PDF, sin el QR). */
    public static function data(SaleCreditPayment $payment)
    {
        $sale = $payment->credit->sale;

        // Historial de pagos del crédito
        $payments = SaleCreditPayment::where('credit_id', $payment->credit_id)
            ->orderBy('created_at')
            ->get()
            ->map(function ($p) {
                return [
                    'fecha' => Carbon::parse($p->created_at)->format('d/m/Y'),
                    'monto' => (int) round($p->amount_paid),
                ];
            });

        $totalPaid = $payments->sum('monto');

        return [
            'cliente'          => $sale->customer->name,
            'fecha'            => now()->format('d/m/Y H:i'),
            'venta_id'         => $sale->id,
            'total_original'   => $sale->total,
            'total_pagado'     => $totalPaid,
            'saldo'            => $sale->total - $totalPaid,
            'pagos'            => $payments,
            'usuario'          => $payment->user->name,
            'estado_credito'   => $payment->credit->status,
            'company'          => Company::first(),
        ];
    }

    /** Dirección pública del comprobante (la que lleva el QR). */
    public static function verifyUrl(SaleCreditPayment $payment)
    {
        return route('creditos.comprobante', base64_encode($payment->id));
    }
}
