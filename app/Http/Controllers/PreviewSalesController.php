<?php

namespace App\Http\Controllers;

use App\Models\PreviewSale;
use Barryvdh\DomPDF\Facade\Pdf;

class PreviewSalesController extends Controller
{
    /**
     * Generar PDF de preventa
     */
    public function generatePDF(PreviewSale $previewSale)
    {
        $pdf = Pdf::loadView('preview-sales.pdf', [
            'previewSale' => $previewSale
        ])->setPaper([0, 0, 226.77, 800], 'portrait'); // 80mm x ancho variable (226.77pt = 80mm)

        return $pdf->stream('preventa-' . $previewSale->id . '.pdf');
    }
}
