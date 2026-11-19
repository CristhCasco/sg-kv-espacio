<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Company;
use App\Services\TicketPdfLayoutService;
use Illuminate\Http\Request;

class InvoicePrintController extends Controller
{
    // Detallada (tu legal actual). Opcional si ya tenés otra ruta.
    public function legal(string $hash)
    {
        return $this->buildInvoice($hash, false);
    }

    // Resumen: “Factura sin detalle”
    public function summary(string $hash)
    {
        return $this->buildInvoice($hash, true);
    }

    /**
     * Genera el PDF; si $isSummary=true, imprime “CONSUMISIÓN” con total.
     */
    private function buildInvoice(string $hash, bool $isSummary)
    {
        $saleId = base64_decode($hash);

        $sale = Sale::with([
            'customer:id,person,company,name,last_name,document_number,dv,address,phone,tax_condition_id',
            'user:id,name',
            'details:id,sale_id,product_id,quantity,price,sub_total',
            'details.product:id,name,tax_rate',
            'company'
        ])->findOrFail($saleId);

        $company = $sale->company ?? Company::first();

        if ($isSummary) {
            // Reemplazar detalles por un único renglón genérico
            $sale->setRelation('details', collect([
                (object)[
                    'product'   => (object)['name' => 'CONSUMISIÓN'],
                    'quantity'  => 1,
                    'sub_total' => $sale->total,
                ]
            ]));
        }

        // Ancho según Configuración (58/80 mm) con alto dinámico
        $paperHeight = app(TicketPdfLayoutService::class)->calculateHeight(
            $sale->details->map(fn($d) => ['name' => $d->product->name ?? 'ITEM']),
            ['headerLines' => 16, 'footerLines' => 14]
        );

        // Usa tu misma vista legal si querés mantener formato
        $pdf = \PDF::loadView('pdf.invoice.invoice_legal', compact('sale', 'company'))
            ->setPaper([0, 0, Company::ticketWidthPt(), $paperHeight]);

        $filename = ($isSummary ? 'Factura-Resumen-' : 'Factura-') .
                    ($sale->invoice_number ?? str_pad($sale->id, 6, '0', STR_PAD_LEFT)) . '.pdf';

        return $pdf->stream($filename);
    }
}
