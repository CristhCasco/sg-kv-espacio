<?php

namespace App\Http\Livewire\Reports\Invoice;


use Livewire\Component;
use App\Models\Sale;
use Carbon\Carbon;

class LegalInvoicesReport extends Component
{
    public $dateFrom, $dateTo;
    public $sales = [];

    public function mount()
    {
        $this->dateFrom = Carbon::now()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    // Botón "Filtrar": la consulta está en render(), solo hace falta volver a renderizar
    public function loadSales()
    {
    }

    public function render()
    {
        $this->sales = Sale::with('customer')
            ->whereNotNull('invoice_number')
            ->whereBetween('created_at', [
                $this->dateFrom . ' 00:00:00',
                $this->dateTo . ' 23:59:59'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.reports.invoice.legal-invoices-report', [
            'sales' => $this->sales
        ])->extends('layouts.theme.app')
        ->section('content');
    }

}
