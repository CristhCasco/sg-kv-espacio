<?php

namespace App\Http\Controllers;

use App\Models\SaleCreditPayment;
use App\Models\Company;
use App\Services\CreditReceiptService;
use App\Services\PrinterNinjaService;
use App\Services\TicketPdfLayoutService;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PdfCreditController extends Controller
{
    // ...existing code...

    // Spanish-compatible wrapper (no route changes required)
    public function imprimirComprobante($hash)
    {
        return $this->printReceipt($hash);
    }

    // Comprobante armado por el sistema para PrinterNinja: se abre como print://<datos>
    public function ninjaPayload($hash)
    {
        $payment = SaleCreditPayment::with(['credit.sale.customer', 'user'])->findOrFail(base64_decode($hash));

        return response(PrinterNinjaService::creditPaymentPayload($payment), 200)
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store');
    }

    // English method with the same logic; passes $company to the view
    public function printReceipt($hash)
    {
        $paymentId = base64_decode($hash);

        $payment = SaleCreditPayment::with(['credit.sale.customer', 'user'])->findOrFail($paymentId);

        $data = CreditReceiptService::data($payment);

        // QR (SVG base64)
        $qrSvg = QrCode::format('svg')->size(90)->generate(CreditReceiptService::verifyUrl($payment));
        $data['qr_base64'] = base64_encode($qrSvg);

        $paperHeight = app(TicketPdfLayoutService::class)->calculateHeight(
            $data['pagos']->map(function ($paymentRow) {
                return [
                    'name' => $paymentRow['fecha'] . ' - ' . number_format($paymentRow['monto'], 0, ',', '.') . ' Gs',
                ];
            }),
            [
                // Medido con DomPDF: cada pago ocupa ~9 pt (en 58 mm, 2 líneas desde 10.000 Gs)
                // y encabezado, firma, pie y QR ~440 pt; incluye el margen de seguridad
                'headerLines' => 22,
                'footerLines' => 19,
                'includeQr' => true,
                'columns' => true,
                'lineHeight' => 9,
                'descChars' => Company::ticketWidth() === 58 ? 20 : 60,
            ]
        );

        $pdf = Pdf::loadView('livewire.tickets.payment_credit', $data)
        ->setPaper([0, 0, Company::ticketWidthPt(), $paperHeight], 'portrait')
        ->setOptions(['defaultFont' => 'monospace', 'isHtml5ParserEnabled' => true]);

        return $pdf->stream("comprobante_pago_credito.pdf");
    }
}