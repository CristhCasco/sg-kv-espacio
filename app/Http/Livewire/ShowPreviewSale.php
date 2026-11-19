<?php

namespace App\Http\Livewire;

use App\Models\PreviewSale;
use App\Models\PreviewSalePayment;
use App\Models\PreviewSaleDetail;
use App\Models\User;
use Livewire\Component;

class ShowPreviewSale extends Component
{
    public PreviewSale $previewSale;
    public $showPaymentModal = false;
    public $payment_type = 'initial';
    public $payment_method = 'cash';
    public $amount = 0;
    public $delivered_by_user_id = '';
    public $delivery_date = '';
    public $payment_notes = '';
    public $editingQuantities = []; // Para editar cantidades

    protected $rules = [
        'amount' => 'required|numeric|min:0.01',
        'payment_type' => 'required|in:initial,partial,full',
        'payment_method' => 'required|in:cash,card,check,transfer',
        'delivered_by_user_id' => 'required|exists:users,id',
        'delivery_date' => 'required|date'
    ];

    public function openPaymentModal()
    {
        if (!$this->previewSale->isActive()) {
            $this->notifyUser('error', 'Solo se pueden agregar pagos a preventas pendientes');
            return;
        }
        $this->showPaymentModal = true;
    }

    public function addPayment()
    {
        $this->validate();

        PreviewSalePayment::create([
            'preview_sale_id' => $this->previewSale->id,
            'amount' => $this->amount,
            'payment_type' => $this->payment_type,
            'payment_method' => $this->payment_method,
            'delivered_by_user_id' => $this->delivered_by_user_id,
            'delivery_date' => $this->delivery_date,
            'notes' => $this->payment_notes
        ]);

        $this->showPaymentModal = false;
        $this->resetPaymentForm();
        $this->previewSale->refresh();
        $this->notifyUser('success', 'Pago registrado exitosamente');
    }

    public function resetPaymentForm()
    {
        $this->amount = 0;
        $this->payment_type = 'initial';
        $this->payment_method = 'cash';
        $this->delivered_by_user_id = '';
        $this->delivery_date = '';
        $this->payment_notes = '';
    }

    public function cancel()
    {
        if (!$this->previewSale->isActive()) {
            $this->notifyUser('error', 'Solo se pueden cancelar preventas pendientes');
            return;
        }

        $this->previewSale->cancel();
        $this->notifyUser('success', 'Preventa cancelada, stock restaurado');
        redirect()->route('preview-sales');
    }

    public function notifyUser($type, $message)
    {
        if ($type === 'error') {
            $this->emit('global-msg', 'ERROR: ' . $message, 2);
        } elseif ($type === 'info') {
            $this->emit('global-msg', 'INFO: ' . $message, 1);
        } else {
            $this->emit('global-msg', $message, 1);
        }
    }

    public function updateQuantity($detailId, $newQuantity)
    {
        $detail = PreviewSaleDetail::find($detailId);
        if (!$detail) {
            $this->notifyUser('error', 'Detalle no encontrado');
            return;
        }

        $product = $detail->product;
        $oldQuantity = $detail->quantity;
        $quantityDiff = $newQuantity - $oldQuantity;

        // Validar stock disponible
        if ($quantityDiff > 0 && $product->stock < $quantityDiff) {
            $this->notifyUser('error', "Stock insuficiente. Disponibles: {$product->stock}");
            return;
        }

        if ($newQuantity < 0.1) {
            $this->notifyUser('error', 'La cantidad mínima es 0.1');
            return;
        }

        // Actualizar stock
        if ($quantityDiff > 0) {
            $product->decrement('stock', $quantityDiff);
            $product->increment('stock_in_trial', $quantityDiff);
        } else {
            $product->increment('stock', abs($quantityDiff));
            $product->decrement('stock_in_trial', abs($quantityDiff));
        }

        // Actualizar cantidad en el detalle
        $detail->update(['quantity' => $newQuantity]);

        // Recalcular total de la preventa
        $this->previewSale->total = $this->previewSale->details->sum(function($item) {
            return $item->quantity * $item->price;
        });
        $this->previewSale->save();

        $this->previewSale->refresh();
        $this->notifyUser('success', 'Cantidad actualizada');
    }

    public function removeProduct($detailId)
    {
        $detail = PreviewSaleDetail::find($detailId);
        if (!$detail) {
            $this->notifyUser('error', 'Detalle no encontrado');
            return;
        }

        $product = $detail->product;
        $quantity = $detail->quantity;

        // Restaurar stock
        $product->increment('stock', $quantity);
        $product->decrement('stock_in_trial', $quantity);

        // Eliminar el detalle
        $detail->delete();

        // Recalcular total
        $this->previewSale->total = $this->previewSale->details->sum(function($item) {
            return $item->quantity * $item->price;
        });
        $this->previewSale->save();

        $this->previewSale->refresh();
        $this->notifyUser('success', 'Producto removido');
    }

    public function goToAddProducts()
    {
        // Guardar la preventa en sesión para editar
        session([
            'editingPreviewSaleId' => $this->previewSale->id,
            'previewSaleCart' => $this->previewSale->details->map(function($detail) {
                return [
                    'id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'barcode' => $detail->product->barcode,
                    'price' => $detail->price,
                    'quantity' => $detail->quantity,
                    'discount' => 0,
                    'notes' => $detail->notes
                ];
            })->toArray()
        ]);
        
        // Redirigir al componente de edición
        return redirect()->route('preview-sales.edit', $this->previewSale);
    }

    public function confirmAndGoToSales()
    {
        if (!$this->previewSale->isActive()) {
            $this->notifyUser('error', 'Solo se pueden procesar preventas pendientes');
            return;
        }

        // Preparar carrito para POS SIN confirmar aún
        $cartItems = [];
        $allDetails = $this->previewSale->details()->with('product')->get();
        
        foreach ($allDetails as $detail) {
            $cartItems[] = [
                'id' => $detail->product_id,
                'barcode' => $detail->product->barcode,
                'name' => $detail->product->name,
                'price' => $detail->price,
                'quantity' => $detail->quantity,
                'w' => $detail->product->is_weighted ? 1 : 0,
                'preview_sale_id' => $this->previewSale->id
            ];
        }

        // Guardar carrito en sesión para POS
        session(['posCart' => collect($cartItems)]);
        session(['preview_sale_customer_id' => $this->previewSale->customer_id]);
        session(['preview_sale_id' => $this->previewSale->id]);
        
        // Redirigir a POS
        return redirect()->route('pos');
    }

    public function render()
    {
        $this->previewSale->load(['customer', 'createdByUser', 'details.product', 'payments.deliveredByUser']);
        
        return view('livewire.preview-sales.show', [
            'users' => User::all()
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
