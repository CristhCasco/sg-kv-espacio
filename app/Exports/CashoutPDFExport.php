<?php

namespace App\Exports;

use Barryvdh\DomPDF\Facade\Pdf;

class CashoutPDFExport
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function generate()
    {
        try {
            $pdf = Pdf::loadView('exports.cashout-pdf', $this->data)
                ->setPaper('a4', 'portrait');
            
            return $pdf;
        } catch (\Exception $e) {
            \Log::error('Error en PDF Export: ' . $e->getMessage());
            throw $e;
        }
    }
}
