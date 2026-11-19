<?php

namespace App\Http\Livewire;

use App\Models\PreviewSale;
use App\Models\PreviewSaleDetail;
use App\Models\Product;
use Livewire\Component;

class EditPreviewSale extends Component
{
    public $previewSale;
    public $cart = [];
    public $totalCart = 0;
    public $totalItems = 0;
    public $showDiscountInput = [];

    protected $listeners = [
        'addProduct',
    ];

    public function mount(PreviewSale $previewSale)
    {
        $this->previewSale = $previewSale;
        
        // Cargar productos desde la sesión o de la preventa
        if (session()->has("previewSaleCart")) {
            $this->cart = collect(session("previewSaleCart"))->toArray();
        } else {
            $this->cart = $previewSale->details->map(function($detail) {
                return [
                    'id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'barcode' => $detail->product->barcode,
                    'price' => \App\Helpers\Num::input($detail->price),
                    'quantity' => \App\Helpers\Num::input($detail->quantity),
                    'notes' => $detail->notes,
                    'discount' => 0,
                    'detail_id' => $detail->id,
                    'is_weighted' => $detail->product->is_weighted ?? false
                ];
            })->toArray();
        }
        $this->updateTotals();
    }

    public function render()
    {
        return view('livewire.preview-sales.edit', [
            'cart' => collect($this->cart)->sortBy('name'),
            'previewSale' => $this->previewSale
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function addProduct($productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->notifyUser('error', 'Producto no encontrado');
            return;
        }

        // Validar que el producto tenga stock disponible
        if ($product->stock <= 0) {
            $this->notifyUser('error', "Producto sin stock disponible");
            return;
        }

        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        
        $existingKey = array_search($product->id, array_column($cartArray, 'id'));
        
        if ($existingKey !== false) {
            $oldQuantity = $cartArray[$existingKey]['quantity'];
            $newQuantity = $oldQuantity + 1;
            // Solo necesita 1 unidad más
            if ($product->stock < 1) {
                $this->notifyUser('error', "Stock insuficiente. Disponibles: {$product->stock}");
                return;
            }
            $cartArray[$existingKey]['quantity'] = $newQuantity;
            $this->notifyUser('success', $product->name . ' cantidad actualizada');
        } else {
            $cartArray[] = [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'price' => $product->price,
                'quantity' => 1,
                'notes' => '',
                'discount' => 0,
                'is_weighted' => $product->is_weighted ?? false
            ];
            $this->notifyUser('success', $product->name . ' agregado al carrito');
        }

        $this->cart = collect($cartArray);
        $this->updateTotals();
    }

    public function updateQuantity($index, $quantity)
    {
        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        
        if (isset($cartArray[$index])) {
            $quantity = max(0.1, floatval($quantity));
            $product = Product::find($cartArray[$index]['id']);
            
            if (!$product) {
                $this->notifyUser('error', 'Producto no encontrado');
                return;
            }

            // Calcular la diferencia con la cantidad anterior
            $oldQuantity = $cartArray[$index]['quantity'];
            $quantityDiff = $quantity - $oldQuantity;

            // Obtener el detalle original para calcular stock disponible real
            $detail_id = $cartArray[$index]['detail_id'] ?? null;
            $original_quantity = $oldQuantity;
            
            if ($detail_id) {
                $detail = PreviewSaleDetail::find($detail_id);
                if ($detail) {
                    $original_quantity = $detail->quantity;
                }
            }

            // Stock disponible = stock actual + lo que teníamos reservado en esta preventa
            $available_stock = $product->stock + $original_quantity;
            
            // Validar stock solo si la nueva cantidad excede lo disponible
            if ($quantity > $available_stock) {
                $needed = $quantity - $available_stock;
                $this->notifyUser('error', "Stock insuficiente. Disponibles: {$available_stock}, Solicitados: {$quantity}");
                return;
            }
            
            // Actualizar stock en BD si es diferencia positiva (agregar más)
            if ($quantityDiff > 0) {
                $product->decrement('stock', $quantityDiff);
                $product->increment('stock_in_trial', $quantityDiff);
            } elseif ($quantityDiff < 0) {
                // Si es negativa (reducir), liberar stock
                $product->increment('stock', abs($quantityDiff));
                $product->decrement('stock_in_trial', abs($quantityDiff));
            }
            
            $cartArray[$index]['quantity'] = $quantity;
            $this->cart = collect($cartArray);
            $this->updateTotals();
        }
    }

    public function removeItem($index)
    {
        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        unset($cartArray[$index]);
        $this->cart = collect(array_values($cartArray));
        $this->updateTotals();
        $this->notifyUser('success', 'Producto removido del carrito');
    }

    public function toggleDiscountInput($index)
    {
        $this->showDiscountInput[$index] = !($this->showDiscountInput[$index] ?? false);
    }

    public function updateDiscount($index, $discount)
    {
        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        if (isset($cartArray[$index])) {
            $cartArray[$index]['discount'] = max(0, floatval($discount));
            $this->cart = collect($cartArray);
            $this->updateTotals();
        }
    }

    public function increaseQuantity($index)
    {
        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        if (isset($cartArray[$index])) {
            $product = Product::find($cartArray[$index]['id']);
            if (!$product) {
                $this->notifyUser('error', 'Producto no encontrado');
                return;
            }

            // Solo necesita 1 unidad más
            if ($product->stock < 1) {
                $this->notifyUser('error', "Stock insuficiente. Disponibles: {$product->stock}");
                return;
            }
            
            $cartArray[$index]['quantity'] = $cartArray[$index]['quantity'] + 1;
            $this->cart = collect($cartArray);
            $this->updateTotals();
        }
    }

    public function decreaseQuantity($index)
    {
        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        if (isset($cartArray[$index])) {
            $quantity = $cartArray[$index]['quantity'] - 1;
            if ($quantity >= 0.1) {
                $cartArray[$index]['quantity'] = $quantity;
                $this->cart = collect($cartArray);
                $this->updateTotals();
            } else {
                $this->notifyUser('info', 'La cantidad mínima es 0.1 unidades');
            }
        }
    }

    public function updateTotals()
    {
        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        $this->totalItems = count($cartArray);
        $this->totalCart = 0;

        foreach ($cartArray as $item) {
            $lineTotal = ($item['price'] - ($item['discount'] ?? 0)) * $item['quantity'];
            $this->totalCart += $lineTotal;
        }
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

    public function save()
    {
        if (empty($this->cart) || count(is_array($this->cart) ? $this->cart : $this->cart->toArray()) === 0) {
            $this->notifyUser('error', 'El carrito está vacío');
            return;
        }

        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        
        // Eliminar detalles antiguos pero MANTENER el stock que ya fue ajustado en tiempo real
        PreviewSaleDetail::where('preview_sale_id', $this->previewSale->id)->delete();

        // Crear nuevos detalles con las cantidades actuales
        $newTotal = 0;
        foreach ($cartArray as $item) {
            PreviewSaleDetail::create([
                'preview_sale_id' => $this->previewSale->id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'notes' => $item['notes'] ?? null
            ]);

            $newTotal += ($item['price'] - ($item['discount'] ?? 0)) * $item['quantity'];
        }

        // Actualizar total de la preventa
        $this->previewSale->update(['total' => $newTotal]);

        session()->forget("previewSaleCart");
        session()->forget("editingPreviewSaleId");
        $this->notifyUser('success', 'Preventa actualizada exitosamente');
        return redirect()->route('preview-sales.show', $this->previewSale);
    }

    public function cancel()
    {
        session()->forget("previewSaleCart");
        session()->forget("editingPreviewSaleId");
        return redirect()->route('preview-sales.show', $this->previewSale);
    }
}
