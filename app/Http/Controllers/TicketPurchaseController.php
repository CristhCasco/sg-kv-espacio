<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Company;
use App\Services\PrinterNinjaService;
use App\Services\PurchaseTicketService;
use App\Services\TicketPdfLayoutService;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketPurchaseController extends Controller
{
    public function imprimirTicket($hash)
    {
        return $this->generarPDF($hash, 'stream');
    }

    // Ticket armado por el sistema para PrinterNinja: se abre como print://<datos>
    public function ninjaPayload($hash)
    {
        $compra = Purchase::with('details.product', 'supplier', 'user')->findOrFail(base64_decode($hash));

        $company = Company::first();
        if (! $company || empty($company->name)) {
            abort(422, 'Por favor configure los datos de la empresa antes de generar comprobantes de compra.');
        }

        return response(PrinterNinjaService::purchasePayload($compra), 200)
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store');
    }

    public function generarTicket($hash)
    {
        return $this->generarPDF($hash, 'download');
    }

    private function generarPDF($hash, $tipo)
    {
        $compra_id = base64_decode($hash);

        $compra = Purchase::with('details.product', 'supplier', 'user')->findOrFail($compra_id);

        // Company validation: do not allow generating purchase tickets if company data is missing
        $company = Company::first();
        if (! $company || empty($company->name)) {
            return redirect()->route('purchases')
                ->with('error', 'Por favor configure los datos de la empresa antes de generar comprobantes de compra.');
        }

        $data = PurchaseTicketService::data($compra, $company);

        $paperHeight = app(TicketPdfLayoutService::class)->calculateHeight($data['productos'], [
            // Medido con DomPDF: encabezado + pie ocupan ~20 líneas y la descripción ~18 caracteres por línea (12 en 58 mm)
            'headerLines' => 11,
            'footerLines' => 9,
            'itemNameKey' => 'nombre',
            'discountKey' => 'descuento_unit',
            'columns' => true,
            'descChars' => Company::ticketWidth() === 58 ? 12 : 18,
        ]);

        $pdf = Pdf::loadView('livewire.tickets.purchase', $data)
            ->setPaper([0, 0, Company::ticketWidthPt(), $paperHeight], 'portrait');

        return $tipo === 'stream'
            ? $pdf->stream("ticket_compra_{$compra->id}.pdf")
            : $pdf->download("ticket_compra_{$compra->id}.pdf");
    }
}