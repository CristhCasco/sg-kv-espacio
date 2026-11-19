<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\Log;


class PurchasesReportController extends Component
{

    public $componentName, $data, $details, $sumDetails, $countDetails,
        $reportType, $userId, $dateFrom, $dateTo, $purchaseId;
    public $statusFilter = 'ALL';
    public $supplierId = 0;
    public $deletePurchaseId = null;

    protected $listeners = ['deletePurchaseConfirmed' => 'deletePurchase'];



    // Aviso si el rango está invertido (antes mostraba el reporte vacío sin explicar por qué)
    public function updatedDateFrom()
    {
        $this->warnIfDateRangeInverted();
    }

    public function updatedDateTo()
    {
        $this->warnIfDateRangeInverted();
    }

    private function warnIfDateRangeInverted()
    {
        if (empty($this->dateFrom) || empty($this->dateTo)) {
            return;
        }

        try {
            if (Carbon::parse($this->dateFrom)->gt(Carbon::parse($this->dateTo))) {
                $this->emit('global-msg', 'La fecha "desde" es posterior a la fecha "hasta": el reporte saldrá vacío');
            }
        } catch (\Exception $e) {
            $this->emit('global-msg', 'La fecha ingresada no es válida');
        }
    }

    public function mount()
    {
        $this->componentName = 'Reportes de Compras';
        $this->data = [];
        $this->details = [];
        $this->sumDetails = 0;
        $this->countDetails = 0;
        $this->reportType = 0;
        $this->userId = 0;
        $this->purchaseId = 0;
        $this->supplierId = 0;

    }

    public function render()
    {

        $this->PurchasesByDate();

        return view('livewire.reports.purchases.purchases-report', [
            'users' => User::orderBy('name', 'asc')->get()
        ])->extends('layouts.theme.app')
            ->section('content');
    }

    public function PurchasesByDate()
    {
        try {
            if ($this->reportType == 0) {
                $from = Carbon::now()->startOfDay();
                $to   = Carbon::now()->endOfDay();
            } else {
                // Validar bien las fechas para evitar errores
                if (empty($this->dateFrom) || empty($this->dateTo)) {
                    $this->data = [];
                    return;
                }
    
                $from = Carbon::parse($this->dateFrom)->startOfDay();
                $to   = Carbon::parse($this->dateTo)->endOfDay();
            }
    
            $query = Purchase::join('users as u', 'u.id', '=', 'purchases.user_id')
                ->join('suppliers as s', 's.id', '=', 'purchases.supplier_id')
                ->select('purchases.*', 'u.name as user', \Illuminate\Support\Facades\DB::raw("CASE WHEN s.person = 'JURIDICA' AND COALESCE(s.company, '') <> '' THEN s.company ELSE TRIM(CONCAT_WS(' ', s.name, s.last_name)) END as supplier"))
                ->whereBetween('purchases.created_at', [$from, $to]);
    
            if ($this->supplierId != 0) {
                $query->where('purchases.supplier_id', $this->supplierId);
            }
    
            if ($this->statusFilter !== 'ALL') {
                $query->where('purchases.status', $this->statusFilter);
            }
    
            $this->data = $query->get();
        } catch (\Exception $e) {
            \Log::error('Error al generar reporte de compras: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->data = [];
            $this->emit('global-msg', 'No se pudo generar el reporte de compras. Revisa las fechas o consulta el log.');
        }
    }
    



    public function getDetails($purchaseId)
    {
        $this->details = PurchaseDetail::join('products as p', 'p.id', 'purchase_details.product_id')
            ->select('purchase_details.id', 'purchase_details.price', 'purchase_details.quantity', 'p.name as product')
            ->where('purchase_details.purchase_id', $purchaseId)
            ->get();


        //
        $suma = $this->details->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $this->sumDetails = $suma;
        $this->countDetails = $this->details->sum('quantity');
        $this->purchaseId = $purchaseId;

        $this->emit('show-modal', 'details loaded');
    }

    public function confirmDelete($id)
    {
        $this->deletePurchaseId = $id;
        $this->dispatchBrowserEvent('confirm-delete-purchase');
    }

    public function deletePurchase()
    {
        if ($this->deletePurchaseId) {
            $purchase = Purchase::find($this->deletePurchaseId);
    
            if (!$purchase) return;
    
            // ⚠️ Si la compra es a crédito y está pendiente, no permitir eliminar
            if ($purchase->status === 'PENDIENTE' && $purchase->payment_type === 'CREDITO') {
                $this->dispatchBrowserEvent('purchase-cannot-delete');
                return;
            }
    
            // Eliminar detalles primero si no hay ON DELETE CASCADE
            PurchaseDetail::where('purchase_id', $this->deletePurchaseId)->delete();
            $purchase->delete();
    
            $this->deletePurchaseId = null;
            $this->PurchasesByDate();
    
            $this->dispatchBrowserEvent('purchase-deleted');
        }
    }
    

}
