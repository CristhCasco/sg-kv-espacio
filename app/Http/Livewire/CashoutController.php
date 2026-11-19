<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use App\Models\ExtraExpense;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class CashoutController extends Component
{
    public $type = 'General'; // General, Ventas, Compras, Utilidades
    public $fromDate, $toDate, $userId, $totalSales, $itemsSales, $sales, $detailsSales;
    public $totalSalesCash = 0, $totalSalesCredit = 0; // Breakdown of sales by type
    public $totalCreditPaid = 0, $totalCreditPending = 0; // Breakdown of paid/pending credits
    public $salesByPaymentMethod = []; // Breakdown by payment method (Sales)
    public $totalPurchases, $itemsPurchases, $purchases, $detailsPurchases;
    public $totalPurchasesCash = 0, $totalPurchasesCredit = 0; // Breakdown of purchases by type
    public $totalCreditPurchasePaid = 0, $totalCreditPurchasePending = 0; // Breakdown of purchase credits
    public $purchasesByPaymentMethod = []; // Breakdown by payment method (Purchases)
    public $totalExtraExpenses = 0, $extraExpenses = [];
    // Utilidades: ventas con costo cargado - costo de lo vendido
    public $utilityRevenue = 0, $utilityCost = 0, $utilityGross = 0, $utilityMargin = 0;
    public $utilityUncostedRevenue = 0, $utilityUncostedProducts = 0;
    public $utilityProducts = [], $utilityByCategory = [], $utilityUncostedList = []; // Detalle (tipo Utilidades)
    public $expenseAmount, $expenseDescription;
    public $paymentType = '', $paymentMethod = ''; // Filtros para Ventas y Compras

    public function mount()
    {
        // Establecer fechas del día actual
        $today = Carbon::now()->format('Y-m-d');
        $this->fromDate = $today;
        $this->toDate = $today;
        $this->userId = 0;
        
        // Ventas
        $this->totalSales = 0;
        $this->itemsSales = 0;
        $this->sales = [];
        $this->detailsSales = [];

        // Compras
        $this->totalPurchases = 0;
        $this->itemsPurchases = 0;
        $this->purchases = [];
        $this->detailsPurchases = [];

        //Gastos Extras 
        $this->totalExtraExpenses = 0;
        $this->extraExpenses = [];
        $this->expenseAmount = 0;
        $this->expenseDescription = '';
        
        // Cargar datos automáticamente
        $this->Consult();
    }

    public function render()
    {
        // Métodos de pago disponibles (estándar del sistema)
        $paymentMethods = collect([
            'EFECTIVO',
            'TDD',        // Tarjeta Débito
            'TDC',        // Tarjeta Crédito
            'TRANS',      // Transferencia
            'TIGO',       // Tigo Money
            'CHEQUE',
            'OTROS'
        ])->sort()->values();

        return view('livewire.cashout.component', [
            'users' => User::orderBy('name', 'asc')->get(),
            'paymentMethods' => $paymentMethods,
        ])->extends('layouts.theme.app')
          ->section('content');
    }

    public function Consult()
    {
        $fi = Carbon::parse($this->fromDate)->format('Y-m-d') . ' 00:00:00';
        $ff = Carbon::parse($this->toDate)->format('Y-m-d') . ' 23:59:59';

        // 🔹 Obtener Ventas (si es General, Ventas o Utilidades)
        if (in_array($this->type, ['General', 'Ventas', 'Utilidades'])) {
            $query = Sale::whereBetween('created_at', [$fi, $ff])
                ->when($this->userId > 0, fn($q) => $q->where('user_id', $this->userId))
                ->where(function ($q) { // Las ventas anuladas no cuentan en el arqueo
                    $q->whereNull('document_status_id')
                      ->orWhereDoesntHave('documentStatus', fn($ds) => $ds->where('name', 'ANULADA'));
                })
                ->with(['credit.payments']); // Eager load relaciones
            
            // Filtros específicos para Ventas
            if ($this->type == 'Ventas') {
                $query->when($this->paymentType != '', fn($q) => $q->where('payment_type', $this->paymentType))
                      ->when($this->paymentMethod != '', fn($q) => $q->where('payment_method', $this->paymentMethod));
            }
            
            $this->sales = $query->get();
            $this->totalSales = $this->sales->sum('total');
            $this->itemsSales = $this->sales->sum('items');
            
            // Breakdown by payment type
            $this->totalSalesCash = $this->sales->where('payment_type', 'CONTADO')->sum('total');
            $this->totalSalesCredit = $this->sales->where('payment_type', 'CREDITO')->sum('total');
            
            // Calculate paid and pending credits
            $creditSales = $this->sales->where('payment_type', 'CREDITO');
            $this->totalCreditPaid = 0;
            $this->totalCreditPending = 0;
            
            foreach ($creditSales as $sale) {
                $saleCredit = $sale->credit; // Relation with SaleCredit
                if ($saleCredit) {
                    $this->totalCreditPaid += $saleCredit->amount_paid;
                    $this->totalCreditPending += $saleCredit->remaining_balance;
                }
            }
            
            // Desglose por método de pago (SOLO LO COBRADO)
            // Estructura: Método => [contado_cobrado, credito_pagado, total_cobrado, credito_pendiente]
            $this->salesByPaymentMethod = [];
            
            // Ventas de Contado - se cobra todo
            $cashSales = $this->sales->where('payment_type', 'CONTADO');
            $cashByMethod = $cashSales->groupBy('payment_method')
                ->map(fn($group) => [
                    'contado' => $group->sum('total'),
                    'credito_pagado' => 0,
                    'total' => $group->sum('total'),
                    'credito_pendiente' => 0
                ])->toArray();
            
            // Ventas de Crédito - agregar solo lo pagado
            $creditSales = $this->sales->where('payment_type', 'CREDITO');
            $creditByMethod = [];
            foreach ($creditSales->groupBy('payment_method') as $method => $group) {
                $creditByMethod[$method] = [
                    'contado' => 0,
                    'credito_pagado' => 0,
                    'total' => 0,
                    'credito_pendiente' => 0
                ];
                
                foreach ($group as $sale) {
                    $saleCredit = $sale->credit;
                    if ($saleCredit) {
                        $creditByMethod[$method]['credito_pagado'] += $saleCredit->amount_paid;
                        $creditByMethod[$method]['credito_pendiente'] += $saleCredit->remaining_balance;
                        $creditByMethod[$method]['total'] += $saleCredit->amount_paid;
                    }
                }
            }
            
            // Combinar ambos
            $this->salesByPaymentMethod = array_merge($cashByMethod, $creditByMethod);
            uasort($this->salesByPaymentMethod, function($a, $b) {
                return $b['total'] <=> $a['total']; // Ordenar descendente por total
            });
        } else {
            $this->sales = [];
            $this->totalSales = 0;
            $this->itemsSales = 0;
            $this->totalSalesCash = 0;
            $this->totalSalesCredit = 0;
            $this->totalCreditPaid = 0;
            $this->totalCreditPending = 0;
            $this->salesByPaymentMethod = [];
        }

        $this->calculateUtilities();

        // 🔹 Obtener Compras (si es General o Compras)
        if ($this->type == 'General' || $this->type == 'Compras') {
            $query = Purchase::whereBetween('created_at', [$fi, $ff])
                ->when($this->userId > 0, fn($q) => $q->where('user_id', $this->userId))
                ->with(['credit.payments']); // Eager load relaciones
            
            // Filtros específicos para Compras
            if ($this->type == 'Compras') {
                $query->when($this->paymentType != '', fn($q) => $q->where('payment_type', $this->paymentType))
                      ->when($this->paymentMethod != '', fn($q) => $q->where('payment_method', $this->paymentMethod));
            }
            
            $this->purchases = $query->get();
            $this->totalPurchases = $this->purchases->sum('total');
            $this->itemsPurchases = $this->purchases->sum('items');
            
            // Breakdown by payment type
            $this->totalPurchasesCash = $this->purchases->where('payment_type', 'CONTADO')->sum('total');
            $this->totalPurchasesCredit = $this->purchases->where('payment_type', 'CREDITO')->sum('total');
            
            // Calculate paid and pending credits
            $creditPurchases = $this->purchases->where('payment_type', 'CREDITO');
            $this->totalCreditPurchasePaid = 0;
            $this->totalCreditPurchasePending = 0;
            
            foreach ($creditPurchases as $purchase) {
                $purchaseCredit = $purchase->credit;
                if ($purchaseCredit) {
                    $this->totalCreditPurchasePaid += $purchaseCredit->amount_paid;
                    $this->totalCreditPurchasePending += $purchaseCredit->remaining_balance;
                }
            }
            
            // Desglose por método de pago (SOLO LO PAGADO)
            // Estructura: Método => [contado_pagado, credito_pagado, total_pagado, credito_pendiente]
            $this->purchasesByPaymentMethod = [];
            
            // Compras de Contado - se paga todo
            $cashPurchases = $this->purchases->where('payment_type', 'CONTADO');
            $cashByMethod = $cashPurchases->groupBy('payment_method')
                ->map(fn($group) => [
                    'contado' => $group->sum('total'),
                    'credito_pagado' => 0,
                    'total' => $group->sum('total'),
                    'credito_pendiente' => 0
                ])->toArray();
            
            // Compras de Crédito - agregar solo lo pagado
            $creditPurchases = $this->purchases->where('payment_type', 'CREDITO');
            $creditByMethod = [];
            foreach ($creditPurchases->groupBy('payment_method') as $method => $group) {
                $creditByMethod[$method] = [
                    'contado' => 0,
                    'credito_pagado' => 0,
                    'total' => 0,
                    'credito_pendiente' => 0
                ];
                
                foreach ($group as $purchase) {
                    $purchaseCredit = $purchase->credit;
                    if ($purchaseCredit) {
                        $creditByMethod[$method]['credito_pagado'] += $purchaseCredit->amount_paid;
                        $creditByMethod[$method]['credito_pendiente'] += $purchaseCredit->remaining_balance;
                        $creditByMethod[$method]['total'] += $purchaseCredit->amount_paid;
                    }
                }
            }
            
            // Combinar ambos
            $this->purchasesByPaymentMethod = array_merge($cashByMethod, $creditByMethod);
            uasort($this->purchasesByPaymentMethod, function($a, $b) {
                return $b['total'] <=> $a['total']; // Ordenar descendente por total
            });
        } else {
            $this->purchases = [];
            $this->totalPurchases = 0;
            $this->itemsPurchases = 0;
            $this->totalPurchasesCash = 0;
            $this->totalPurchasesCredit = 0;
            $this->totalCreditPurchasePaid = 0;
            $this->totalCreditPurchasePending = 0;
            $this->purchasesByPaymentMethod = [];
        }

        // 🔹 Obtener Gastos Extras (General y Utilidades, para la utilidad neta)
        if ($this->type == 'General' || $this->type == 'Utilidades') {
            $this->extraExpenses = ExtraExpense::whereBetween('date', [$fi, $ff])
                ->when($this->userId > 0, fn($q) => $q->where('user_id', $this->userId))
                ->get();
            $this->totalExtraExpenses = $this->extraExpenses->sum('amount');
        } else {
            $this->extraExpenses = [];
            $this->totalExtraExpenses = 0;
        }
    }


    /**
     * Utilidad de lo vendido: subtotal de cada línea menos su costo guardado al vender.
     * Las ventas a crédito cuentan al vender; las anuladas ya vienen excluidas en $this->sales.
     * Las líneas sin costo se excluyen del cálculo y se informan aparte.
     * General muestra solo el resumen; Utilidades agrega el detalle por producto y categoría.
     */
    private function calculateUtilities()
    {
        $this->utilityRevenue = 0;
        $this->utilityCost = 0;
        $this->utilityGross = 0;
        $this->utilityMargin = 0;
        $this->utilityUncostedRevenue = 0;
        $this->utilityUncostedProducts = 0;
        $this->utilityProducts = [];
        $this->utilityByCategory = [];
        $this->utilityUncostedList = [];

        $saleIds = collect($this->sales)->pluck('id');
        if (!in_array($this->type, ['General', 'Utilidades']) || $saleIds->isEmpty()) {
            return;
        }

        $rows = SaleDetail::join('products as p', 'p.id', 'sale_details.product_id')
            ->leftJoin('categories as c', 'c.id', 'p.category_id')
            ->whereIn('sale_details.sale_id', $saleIds)
            ->groupBy('sale_details.product_id', 'p.name', 'c.name')
            ->select(
                'p.name as product',
                'c.name as category',
                DB::raw('SUM(sale_details.quantity) as quantity'),
                DB::raw('SUM(CASE WHEN sale_details.cost > 0 THEN sale_details.sub_total ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN sale_details.cost > 0 THEN sale_details.cost * sale_details.quantity ELSE 0 END) as cost'),
                DB::raw('SUM(CASE WHEN sale_details.cost > 0 THEN 0 ELSE sale_details.sub_total END) as uncosted_revenue')
            )
            ->get();

        foreach ($rows as $row) {
            $this->utilityRevenue += (float) $row->revenue;
            $this->utilityCost += (float) $row->cost;
            if ((float) $row->uncosted_revenue > 0) {
                $this->utilityUncostedRevenue += (float) $row->uncosted_revenue;
                $this->utilityUncostedProducts++;
            }
        }

        $this->utilityGross = $this->utilityRevenue - $this->utilityCost;
        $this->utilityMargin = $this->margin($this->utilityRevenue, $this->utilityGross);

        if ($this->type != 'Utilidades') {
            return;
        }

        $costed = $rows->filter(fn($row) => (float) $row->revenue > 0);

        $this->utilityProducts = $costed
            ->map(fn($row) => [
                'product' => $row->product,
                'category' => $row->category ?? 'Sin categoría',
                'quantity' => (float) $row->quantity,
                'revenue' => (float) $row->revenue,
                'cost' => (float) $row->cost,
                'utility' => (float) $row->revenue - (float) $row->cost,
                'margin' => $this->margin((float) $row->revenue, (float) $row->revenue - (float) $row->cost),
            ])
            ->sortByDesc('utility')
            ->values()
            ->toArray();

        $this->utilityByCategory = collect($this->utilityProducts)
            ->groupBy('category')
            ->map(fn($group, $category) => [
                'category' => $category,
                'revenue' => $group->sum('revenue'),
                'cost' => $group->sum('cost'),
                'utility' => $group->sum('utility'),
                'margin' => $this->margin($group->sum('revenue'), $group->sum('utility')),
            ])
            ->sortByDesc('utility')
            ->values()
            ->toArray();

        $this->utilityUncostedList = $rows
            ->filter(fn($row) => (float) $row->uncosted_revenue > 0)
            ->map(fn($row) => [
                'product' => $row->product,
                'quantity' => (float) $row->quantity,
                'revenue' => (float) $row->uncosted_revenue,
            ])
            ->sortByDesc('revenue')
            ->values()
            ->toArray();
    }

    private function margin($revenue, $utility)
    {
        return $revenue > 0 ? ($utility / $revenue) * 100 : 0;
    }

    public function viewUtilities()
    {
        $this->type = 'Utilidades';
        $this->Consult();
    }

    public function viewLessUtilities()
    {
        $this->type = 'General';
        $this->Consult();
    }

    public function AddExpense()
    {
        $this->validate([
            'expenseAmount' => 'required|numeric|min:0.01',
            'expenseDescription' => 'required|string|max:255',
        ]);

        ExtraExpense::create([
            'user_id' => Auth::id(),
            'amount' => $this->expenseAmount,
            'description' => $this->expenseDescription,
            'date' => now()->format('Y-m-d'),
        ]);

        $this->expenseAmount = 0;
        $this->expenseDescription = '';

        $this->Consult(); // Actualizar datos después de agregar un gasto

        $this->dispatchBrowserEvent('close-modal');
    }

    public function viewDetails(Sale $sale)
    {
        $fi = Carbon::parse($this->fromDate)->format('Y-m-d') . ' 00:00:00';
        $ff = Carbon::parse($this->toDate)->format('Y-m-d') . ' 23:59:59';

        $this->detailsSales = Sale::join('sale_details as d', 'd.sale_id', 'sales.id')
            ->join('products as p', 'p.id', 'd.product_id')
            ->join('customers as c', 'c.id', 'sales.customer_id') // Unir con la tabla customers
            ->select('d.sale_id', 'p.name as product', 'd.quantity', 'd.price', 'c.name as customer') // Obtener el nombre del cliente
            ->whereBetween('sales.created_at', [$fi, $ff])
            ->where('sales.status', 'PAGADO')
            ->where('sales.user_id', $this->userId)
            ->where('sales.id', $sale->id)
            ->get();

        $this->emit('show-modal', 'modal-details-sales');
    }

    public function viewPurchaseDetails(Purchase $purchase)
    {
        $fi = Carbon::parse($this->fromDate)->format('Y-m-d') . ' 00:00:00';
        $ff = Carbon::parse($this->toDate)->format('Y-m-d') . ' 23:59:59';

        $this->detailsPurchases = Purchase::join('purchase_details as d', 'd.purchase_id', 'purchases.id')
            ->join('products as p', 'p.id', 'd.product_id')
            ->join('suppliers as s', 's.id', 'purchases.supplier_id') // Unir con la tabla suppliers
            ->select('d.purchase_id', 'p.name as product', 'd.quantity', 'd.price', \Illuminate\Support\Facades\DB::raw("CASE WHEN s.person = 'JURIDICA' AND COALESCE(s.company, '') <> '' THEN s.company ELSE TRIM(CONCAT_WS(' ', s.name, s.last_name)) END as supplier")) // Obtener el nombre del proveedor
            ->whereBetween('purchases.created_at', [$fi, $ff])
            ->where('purchases.status', 'PAGADO')
            ->where('purchases.user_id', $this->userId)
            ->where('purchases.id', $purchase->id)
            ->get();

        $this->emit('show-modal', 'modal-details-purchases');
    }

    public function exportExcel()
    {
        // Ejecutar la consulta nuevamente para tener datos actualizados
        $this->Consult();

        // Obtener nombre del usuario si está filtrado
        $userName = 'Todos';
        if ($this->userId > 0) {
            $user = User::find($this->userId);
            $userName = $user ? $user->name : 'Todos';
        }

        // Crear un array con los datos a exportar
        $data = [
            'type' => $this->type,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
            'userId' => $this->userId,
            'userName' => $userName,
            'paymentType' => $this->paymentType ?: 'Todos',
            'paymentMethod' => $this->paymentMethod ?: 'Todos',
            'totalSales' => $this->totalSales,
            'itemsSales' => $this->itemsSales,
            'totalSalesCash' => $this->totalSalesCash,
            'totalSalesCredit' => $this->totalSalesCredit,
            'totalCreditPaid' => $this->totalCreditPaid,
            'totalCreditPending' => $this->totalCreditPending,
            'salesByPaymentMethod' => $this->salesByPaymentMethod,
            'totalPurchases' => $this->totalPurchases,
            'itemsPurchases' => $this->itemsPurchases,
            'totalPurchasesCash' => $this->totalPurchasesCash,
            'totalPurchasesCredit' => $this->totalPurchasesCredit,
            'totalCreditPurchasePaid' => $this->totalCreditPurchasePaid,
            'totalCreditPurchasePending' => $this->totalCreditPurchasePending,
            'purchasesByPaymentMethod' => $this->purchasesByPaymentMethod,
            'totalExtraExpenses' => $this->totalExtraExpenses,
            'utilityRevenue' => $this->utilityRevenue,
            'utilityCost' => $this->utilityCost,
            'utilityGross' => $this->utilityGross,
            'utilityMargin' => $this->utilityMargin,
            'utilityUncostedRevenue' => $this->utilityUncostedRevenue,
            'utilityUncostedProducts' => $this->utilityUncostedProducts,
            'utilityProducts' => $this->utilityProducts,
            'utilityByCategory' => $this->utilityByCategory,
            'utilityUncostedList' => $this->utilityUncostedList,
            'sales' => $this->sales,
            'purchases' => $this->purchases,
            'extraExpenses' => $this->extraExpenses,
        ];

        // Generar nombre del archivo
        $fileName = 'Corte_Caja_' . $this->type . '_' . $this->fromDate . '_' . $this->toDate . '.xlsx';

        // Exportar usando una clase de exportación
        return Excel::download(new \App\Exports\CashoutExport($data), $fileName);
    }

    public function exportPDF()
    {
        // Ejecutar la consulta nuevamente para tener datos actualizados
        $this->Consult();

        // Obtener nombre del usuario si está filtrado
        $userName = 'Todos';
        if ($this->userId > 0) {
            $user = User::find($this->userId);
            $userName = $user ? $user->name : 'Todos';
        }

        // Crear un array con los datos a exportar
        $data = [
            'type' => $this->type,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
            'userId' => $this->userId,
            'userName' => $userName,
            'paymentType' => $this->paymentType ?: 'Todos',
            'paymentMethod' => $this->paymentMethod ?: 'Todos',
            'totalSales' => $this->totalSales,
            'itemsSales' => $this->itemsSales,
            'totalSalesCash' => $this->totalSalesCash,
            'totalSalesCredit' => $this->totalSalesCredit,
            'totalCreditPaid' => $this->totalCreditPaid,
            'totalCreditPending' => $this->totalCreditPending,
            'salesByPaymentMethod' => $this->salesByPaymentMethod,
            'totalPurchases' => $this->totalPurchases,
            'itemsPurchases' => $this->itemsPurchases,
            'totalPurchasesCash' => $this->totalPurchasesCash,
            'totalPurchasesCredit' => $this->totalPurchasesCredit,
            'totalCreditPurchasePaid' => $this->totalCreditPurchasePaid,
            'totalCreditPurchasePending' => $this->totalCreditPurchasePending,
            'purchasesByPaymentMethod' => $this->purchasesByPaymentMethod,
            'totalExtraExpenses' => $this->totalExtraExpenses,
            'utilityRevenue' => $this->utilityRevenue,
            'utilityCost' => $this->utilityCost,
            'utilityGross' => $this->utilityGross,
            'utilityMargin' => $this->utilityMargin,
            'utilityUncostedRevenue' => $this->utilityUncostedRevenue,
            'utilityUncostedProducts' => $this->utilityUncostedProducts,
            'utilityProducts' => $this->utilityProducts,
            'utilityByCategory' => $this->utilityByCategory,
            'utilityUncostedList' => $this->utilityUncostedList,
            'sales' => $this->sales,
            'purchases' => $this->purchases,
            'extraExpenses' => $this->extraExpenses,
        ];

        // Guardar datos en sesión
        session(['cashout_pdf_data' => $data]);
        
        // Emitir evento para descargar PDF
        $this->emit('downloadPDF');
    }
}
