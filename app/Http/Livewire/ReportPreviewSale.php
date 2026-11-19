<?php

namespace App\Http\Livewire;

use App\Models\PreviewSale;
use Livewire\Component;

class ReportPreviewSale extends Component
{
    public $status = '';
    public $date_from = '';
    public $date_to = '';

    public function render()
    {
        $query = PreviewSale::with(['customer', 'details', 'payments']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->date_from) {
            $query->whereDate('created_at', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->whereDate('created_at', '<=', $this->date_to);
        }

        $previewSales = $query->get();

        $stats = [
            'total_pending' => $previewSales->where('status', 'pending')->count(),
            'total_confirmed' => $previewSales->where('status', 'confirmed')->count(),
            'total_cancelled' => $previewSales->where('status', 'cancelled')->count(),
            'total_amount' => $previewSales->sum('total'),
            'total_paid' => $previewSales->sum(function($p) { return $p->total_paid ?? 0; })
        ];

        return view('livewire.preview-sales.report', [
            'previewSales' => $previewSales,
            'stats' => $stats
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
