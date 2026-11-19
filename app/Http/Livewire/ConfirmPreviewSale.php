<?php

namespace App\Http\Livewire;

use App\Models\PreviewSale;
use App\Models\PreviewSaleDetail;
use App\Models\Product;
use Livewire\Component;

class ConfirmPreviewSale extends Component
{
    public PreviewSale $previewSale;
    public $items = [];
    public $newProducts = [['product_id' => '', 'quantity' => 0, 'notes' => '']];
    public $total = 0;

    public function mount()
    {
        $this->loadItems();
    }

    public function loadItems()
    {
        $this->items = $this->previewSale->details->map(fn($detail) => [
            'id' => $detail->id,
            'product_id' => $detail->product_id,
            'quantity' => \App\Helpers\Num::input($detail->quantity), // decimal: sin "1.000" en el campo
            'price' => \App\Helpers\Num::input($detail->price),
            'product_name' => $detail->product->name
        ])->toArray();

        $this->calculateTotal();
    }

    public function updated($propertyName)
    {
        $this->calculateTotal();
    }

    public function removeItem($detailId)
    {
        $detail = PreviewSaleDetail::find($detailId);
        if ($detail) {
            $detail->product->increment('stock', $detail->quantity);
            $detail->product->decrement('stock_in_trial', $detail->quantity);
            $detail->delete();
        }

        $this->items = array_filter($this->items, fn($item) => $item['id'] != $detailId);
        $this->items = array_values($this->items);
        $this->calculateTotal();
    }

    public function addNewProduct()
    {
        $this->newProducts[] = ['product_id' => '', 'quantity' => 0, 'notes' => ''];
    }

    public function removeNewProduct($index)
    {
        unset($this->newProducts[$index]);
        $this->newProducts = array_values($this->newProducts);
    }

    public function calculateTotal()
    {
        $this->total = 0;

        foreach ($this->items as $item) {
            $this->total += $item['quantity'] * $item['price'];
        }

        foreach ($this->newProducts as $item) {
            if ($item['product_id'] && $item['quantity']) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $this->total += $item['quantity'] * $product->price;
                }
            }
        }
    }

    public function confirm()
    {
        // Actualizar items existentes
        foreach ($this->items as $item) {
            $detail = PreviewSaleDetail::find($item['id']);
            if ($detail) {
                $difference = $item['quantity'] - $detail->quantity;
                
                if ($difference != 0) {
                    if ($difference > 0) {
                        $detail->product->decrement('stock', $difference);
                        $detail->product->increment('stock_in_trial', $difference);
                    } else {
                        $detail->product->increment('stock', abs($difference));
                        $detail->product->decrement('stock_in_trial', abs($difference));
                    }
                }
                
                $detail->update(['quantity' => $item['quantity']]);
            }
        }

        // Agregar nuevos productos
        foreach ($this->newProducts as $item) {
            if ($item['product_id'] && $item['quantity'] > 0) {
                $product = Product::find($item['product_id']);
                
                PreviewSaleDetail::create([
                    'preview_sale_id' => $this->previewSale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'notes' => $item['notes'] ?? null
                ]);

                $product->decrement('stock', $item['quantity']);
                $product->increment('stock_in_trial', $item['quantity']);
            }
        }

        $this->previewSale->confirm();
        $this->previewSale->update(['total' => $this->total]);

        // Preparar carrito para POS
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

        $this->emit('notify', ['type' => 'success', 'message' => 'Preventa confirmada. Redirigiendo a ventas...']);
        
        // Redirigir a POS
        return redirect()->route('pos');
    }

    public function render()
    {
        return view('livewire.preview-sales.confirm', [
            'products' => Product::all()
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
