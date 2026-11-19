<?php

namespace App\Http\Livewire;

use App\Models\PreviewSale;
use App\Models\Customer;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Session;

class PreviewSalesController extends Component
{
    use WithPagination;

    public $status = '';
    public $customer_id = '';
    public $date_from = '';
    public $date_to = '';

    protected $queryString = ['status', 'customer_id', 'date_from', 'date_to'];

    public function render()
    {
        $previewSales = PreviewSale::with(['customer', 'createdByUser', 'details', 'payments'])
            ->orderBy('created_at', 'desc');

        if ($this->status) {
            $previewSales->where('status', $this->status);
        }

        if ($this->customer_id) {
            $previewSales->where('customer_id', $this->customer_id);
        }

        if ($this->date_from) {
            $previewSales->whereDate('created_at', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $previewSales->whereDate('created_at', '<=', $this->date_to);
        }

        return view('livewire.preview-sales.index', [
            'previewSales' => $previewSales->paginate(15),
            'customers' => Customer::all()
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function cancel(PreviewSale $previewSale)
    {
        if (!$previewSale->isActive()) {
            $this->emit('notify', ['type' => 'error', 'message' => 'Solo se pueden cancelar preventas pendientes']);
            return;
        }

        $previewSale->cancel();
        $this->emit('notify', ['type' => 'success', 'message' => 'Preventa cancelada, stock restaurado']);
    }

    public function quickConfirm(PreviewSale $previewSale)
    {
        if (!$previewSale->isActive()) {
            return redirect()->back()->with('error', 'Solo se pueden procesar preventas pendientes');
        }

        // Preparar carrito para POS
        $cartItems = [];
        foreach ($previewSale->details as $detail) {
            $cartItems[] = [
                'id' => $detail->product_id,
                'barcode' => $detail->product->barcode,
                'name' => $detail->product->name,
                'price' => $detail->price,
                'quantity' => $detail->quantity,
                'w' => $detail->product->is_weighted ? 1 : 0,
                'preview_sale_id' => $previewSale->id
            ];
        }

        // Guardar carrito en sesión para POS
        Session::put('posCart', collect($cartItems));
        Session::put('preview_sale_customer_id', $previewSale->customer_id);
        Session::put('preview_sale_id', $previewSale->id);

        // Redirigir a POS
        return redirect()->route('pos');
    }
}
