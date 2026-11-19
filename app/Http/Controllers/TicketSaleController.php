<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Company;
use App\Services\PrinterNinjaService;
use App\Services\SaleTicketService;
use App\Services\TicketPdfLayoutService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TicketSaleController extends Controller
{
    public function printTicket($hash)
    {
        if (!$hash || !($saleId = base64_decode($hash, true))) {
            abort(404, 'Invalid ID');
        }

        $sale = Sale::with('details.product', 'customer')->findOrFail($saleId);
        $company = Company::first();

        $products = SaleTicketService::products($sale);

        return view('livewire.tickets.sale', array_merge([
            'date'                   => Carbon::parse($sale->created_at)->format('d/m/Y H:i'),
            'company'                => $company,
            'customer'               => optional($sale->customer)->display_name ?? 'Without customer',
            'sale_id'                => $sale->id,
            'payment_type'           => $sale->payment_type,
            'products'               => $products,
            'qr_base64'              => null,
            'auto_print'             => true,
        ], SaleTicketService::totals($sale, $products)));
    }

    // Ticket armado por el sistema para PrinterNinja: se abre como print://<datos>
    public function ninjaPayload($hash)
    {
        if (!$hash || !($saleId = base64_decode($hash, true))) {
            abort(404, 'Invalid ID');
        }

        $sale = Sale::with('details.product', 'customer', 'user')->findOrFail($saleId);

        return response(PrinterNinjaService::salePayload($sale), 200)
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store');
    }

    // Facturas armadas por el sistema para PrinterNinja (con detalle y sin detalle)
    public function invoiceNinjaPayload($hash)
    {
        return $this->plainPayload(PrinterNinjaService::invoicePayload(base64_decode($hash)));
    }

    public function invoiceSummaryNinjaPayload($hash)
    {
        return $this->plainPayload(PrinterNinjaService::invoicePayload(base64_decode($hash), true));
    }

    private function plainPayload($payload)
    {
        return response($payload, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store');
    }

    public function downloadTicket($hash)
    {
        return $this->generatePDF($hash, 'download');
    }

    private function generatePDF($hash, $type)
    {
        if (!$hash || !($saleId = base64_decode($hash, true))) {
            abort(404, 'Invalid ID');
        }

        $sale = Sale::with('details.product', 'customer')->findOrFail($saleId);
        $company = Company::first(); // 👈 Obtener la empresa de la BD

        $products = SaleTicketService::products($sale);

        // ✅ QR en SVG embebido
        $url = route('pos.ticket', base64_encode($sale->id));
        $qrSvg = QrCode::format('svg')->size(100)->generate($url);
        $qrBase64 = base64_encode($qrSvg);

        $paperHeight = app(TicketPdfLayoutService::class)->calculateHeight($products, [
            // Medido con DomPDF: encabezado + pie ocupan ~16 líneas y la descripción ~22 caracteres por línea
            'headerLines' => 9,
            'footerLines' => 7,
            // El QR de la vista está comentado (<!-- -->): no ocupa lugar en el ticket
            'includeQr' => false,
            'columns' => true,
            'descChars' => Company::ticketWidth() === 58 ? 14 : 22,
        ]);

        $pdf = Pdf::loadView('livewire.tickets.sale', array_merge([
            'date'                => Carbon::parse($sale->created_at)->format('d/m/Y H:i'),
            'company'             => $company, // 👈 Pasar la empresa completa
            'customer'            => optional($sale->customer)->display_name ?? 'Without customer',
            'sale_id'             => $sale->id,
            'payment_type'        => $sale->payment_type,
            'products'            => $products,
            'qr_base64'           => $qrBase64,
        ], SaleTicketService::totals($sale, $products)))
        ->setPaper([0, 0, Company::ticketWidthPt(), $paperHeight], 'portrait')
        ->setOptions(['defaultFont' => 'monospace', 'isHtml5ParserEnabled' => true]);

        return $type === 'stream'
            ? $pdf->stream("ticket_sale_{$sale->id}.pdf")
            : $pdf->download("ticket_sale_{$sale->id}.pdf");
    }
}