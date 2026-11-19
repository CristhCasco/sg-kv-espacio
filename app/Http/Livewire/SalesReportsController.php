<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Livewire\Component;
use App\Models\SaleDetail;
use Livewire\WithPagination;
use Illuminate\Http\Request;
use App\Services\DocumentCancelService;

class SalesReportsController extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $componentName, $details, $sumDetails, $countDetails,
    $reportType, $userId, $dateFrom, $dateTo, $saleId;
    public $ahorroTotal;
    public $customerId = 0;
    public $deleteSaleId = null;
    public $statusFilter = 'ALL';

    protected $listeners = ['deleteSaleDirect' => 'deleteSale'];

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
        $this->componentName ='Reportes de Ventas';
        $this->details =[];
        $this->sumDetails =0;
        $this->countDetails =0;
        $this->reportType =0;
        $this->userId =0;
        $this->saleId =0;
        $this->customerId = 0;
        $this->statusFilter = 'ALL';
    }

    public function render()
    {
        $sales  = $this->SalesByDate();
        $totals = $this->getTotals();

        return view('livewire.reports.sales.component', [
            'users'  => User::orderBy('name','asc')->get(),
            'data'   => $sales,
            'totals' => $totals
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function SalesByDate()
    {
        if ($this->reportType == 0) {
            $from = Carbon::now()->startOfDay();
            $to   = Carbon::now()->endOfDay();
        } else {
            if (empty($this->dateFrom) || empty($this->dateTo)) {
                return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            }

            try {
                $from = Carbon::parse($this->dateFrom)->startOfDay();
                $to   = Carbon::parse($this->dateTo)->endOfDay();
            } catch (\Exception $e) {
                return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            }
        }

        $query = Sale::join('users as u', 'u.id', '=', 'sales.user_id')
            ->join('customers as c', 'c.id', '=', 'sales.customer_id')
            ->leftJoin('document_statuses as ds', 'ds.id', '=', 'sales.document_status_id')
            ->leftJoin('invoice_types as it', 'it.id', '=', 'sales.invoice_type_id')
            ->leftJoin('users as uc', 'uc.id', 'sales.cancelled_by')
            ->select(
                'sales.*',
                'u.name as usuario',
                'c.name as customer',
                'ds.name as document_status',
                'it.name as invoice_type_name',
                'uc.name as usuario_cancelo'
            )
            ->whereBetween('sales.created_at', [$from, $to]);

        if ($this->userId > 0) {
            $query->where('sales.user_id', $this->userId);
        }

        if ($this->customerId > 0) {
            $query->where('sales.customer_id', $this->customerId);
        }

        if ($this->statusFilter !== 'ALL') {
            $query->where('sales.status', $this->statusFilter);
        }

        return $query->orderBy('sales.id', 'desc')->paginate(10);
    }


    public function getTotals()
    {
        if ($this->reportType == 0) {
            $from = Carbon::now()->startOfDay();
            $to   = Carbon::now()->endOfDay();
        } else {
            if (empty($this->dateFrom) || empty($this->dateTo)) {
                return collect();
            }

            try {
                $from = Carbon::parse($this->dateFrom)->startOfDay();
                $to   = Carbon::parse($this->dateTo)->endOfDay();
            } catch (\Exception $e) {
                return collect();
            }
        }

        $query = Sale::join('users as u', 'u.id', '=', 'sales.user_id')
            ->join('customers as c', 'c.id', '=', 'sales.customer_id')
            ->leftJoin('document_statuses as ds', 'ds.id', '=', 'sales.document_status_id')
            ->leftJoin('invoice_types as it', 'it.id', '=', 'sales.invoice_type_id')
            ->leftJoin('users as uc', 'uc.id', 'sales.cancelled_by')
            ->select(
                'sales.*',
                'u.name as usuario',
                'c.name as customer',
                'ds.name as document_status',
                'it.name as invoice_type_name',
                'uc.name as usuario_cancelo'
            )
            ->whereBetween('sales.created_at', [$from, $to]);

        if ($this->userId > 0) {
            $query->where('sales.user_id', $this->userId);
        }

        if ($this->customerId > 0) {
            $query->where('sales.customer_id', $this->customerId);
        }

        if ($this->statusFilter !== 'ALL') {
            $query->where('sales.status', $this->statusFilter);
        }

        // SOLO documentos activos (no anulados)
        $query->where('ds.name', '!=', 'ANULADA');

        return $query->get();
    }

    public function getDetails($saleId)
    {
        $this->details = SaleDetail::join('products as p','p.id','sale_details.product_id')
        ->select(
            'sale_details.id',
            'sale_details.price',
            'sale_details.quantity',
            'sale_details.manual_discount',
            'p.name as product',
            'p.is_weighted'
        )
        ->where('sale_details.sale_id', $saleId)
        ->get();

        $suma = $this->details->sum(function($item) {
            $descuento = floatval($item->manual_discount ?? 0);
            $precioFinal = max(floatval($item->price) - $descuento, 0);
            return $precioFinal * $item->quantity;
        });

        $ahorro = $this->details->sum(function($item) {
            return floatval($item->manual_discount ?? 0) * $item->quantity;
        });

        $this->sumDetails = $suma;
        $this->countDetails = $this->details->sum('quantity');
        $this->saleId = $saleId;
        $this->ahorroTotal = $ahorro;

        $this->emit('show-modal','details loaded');
    }

    public function inventory(Request $request)
    {
        $stockFilter = $request->get('stock_filter', 'all');
        $productTypeFilter = $request->get('product_type', 'all');
        $productNameFilter = trim((string) $request->get('product_name', ''));
        $categoryFilter = (int) $request->get('category_id', 0);

        $query = Product::query();

        if ($stockFilter === 'minimo') {
            $query->whereRaw('COALESCE(stock,0) <= COALESCE(min_stock,0)');
        }

        if ($productTypeFilter === 'peso') {
            $query->where('is_weighted', 1);
        } elseif ($productTypeFilter === 'unidad') {
            $query->where('is_weighted', 0);
        }

        if ($productNameFilter !== '') {
            $query->where('name', 'like', '%' . $productNameFilter . '%');
        }

        if ($categoryFilter > 0) {
            $query->where('category_id', $categoryFilter);
        }

        $products = $query->orderBy('name')->get();
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('livewire.reports.inventory.inventory', compact('products', 'stockFilter', 'productTypeFilter', 'productNameFilter', 'categoryFilter', 'categories'));
    }

    public function confirmDelete($id)
    {
        $this->deleteSaleId = $id;
        $this->dispatchBrowserEvent('confirm-delete-sale');
    }

    public function cancelSale($saleId)
    {
        try {
            $sale = \App\Models\Sale::with('details', 'documentStatus')->findOrFail($saleId);

            if (optional($sale->documentStatus)->name === 'ANULADA') {
                $this->dispatchBrowserEvent('notification', [
                    'type' => 'info',
                    'message' => 'Esta venta ya fue anulada previamente.'
                ]);
                return;
            }

            $result = DocumentCancelService::cancel($sale, 'sale');

            // 🔍 DEBUG: Ver qué retorna el servicio
            \Log::info('DocumentCancelService result:', [
                'sale_id' => $saleId,
                'result' => $result
            ]);

            if ($result['status'] === 'success') {
                $sale->update([
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                ]);
                
                // Refrescar la página
                $this->emit('refresh');
            }

            $this->dispatchBrowserEvent('notification', [
                'type' => $result['status'],
                'message' => $result['message'] ?? 'Error desconocido'
            ]);

        } catch (\Throwable $e) {
            \Log::error('Error crítico al anular venta: ' . $e->getMessage(), [
                'sale_id' => $saleId,
                'user' => auth()->user()->name ?? 'desconocido',
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatchBrowserEvent('notification', [
                'type' => 'error',
                'message' => 'Ocurrió un error al intentar anular esta venta.'
            ]);
        }
    }
}
