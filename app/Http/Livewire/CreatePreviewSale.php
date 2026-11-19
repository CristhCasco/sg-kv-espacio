<?php

namespace App\Http\Livewire;

use App\Models\PreviewSale;
use App\Models\PreviewSaleDetail;
use App\Models\Product;
use App\Models\Customer;
use Livewire\Component;
use Illuminate\Support\Collection;

class CreatePreviewSale extends Component
{
    public $cart = [];
    public $totalCart = 0;
    public $totalItems = 0;
    public $customerId = '';
    public $customerName = null;
    public $customers = [];
    public $expected_delivery_date = '';
    public $notes = '';
    public $showDiscountInput = [];

    protected $listeners = [
        'setCustomer',
        'addProduct',
        'removeItem',
        'clearCart'
    ];

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

    public function mount()
    {
        $this->customers = Customer::orderBy('name', 'ASC')->get();
        if (session()->has("previewCart")) {
            $this->cart = collect(session("previewCart"))->toArray();
        } else {
            $this->cart = collect([]);
        }
        $this->updateTotals();
    }

    public function render()
    {
        return view('livewire.preview-sales.create', [
            'customers' => $this->customers,
            'cart' => collect($this->cart)->sortBy('name')
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function setCustomer($customerId)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->customerId = $customer->id;
            $this->customerName = $customer->display_name;
            $this->notifyUser('success', 'Cliente seleccionado: ' . $customer->display_name);
        }
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
        session(["previewCart" => $this->cart]);
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

            // Validar stock solo si necesita más unidades
            if ($quantityDiff > 0 && $quantityDiff > $product->stock) {
                $this->notifyUser('error', "Stock insuficiente. Disponibles: {$product->stock}, Necesarios: {$quantityDiff}");
                return;
            }
            
            $cartArray[$index]['quantity'] = $quantity;
            $this->cart = collect($cartArray);
            session(["previewCart" => $this->cart]);
            $this->updateTotals();
        }
    }

    public function removeItem($index)
    {
        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        unset($cartArray[$index]);
        $this->cart = collect(array_values($cartArray));
        session(["previewCart" => $this->cart]);
        $this->updateTotals();
        $this->notifyUser('success', 'Producto removido del carrito');
    }

    public function clearCart()
    {
        $this->cart = collect([]);
        session()->forget("previewCart");
        $this->updateTotals();
        $this->notifyUser('info', 'Carrito vaciado');
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
            session(["previewCart" => $this->cart]);
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
            session(["previewCart" => $this->cart]);
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
                session(["previewCart" => $this->cart]);
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

    public function store()
    {
        if (!$this->customerId) {
            $this->notifyUser('error', 'Debes seleccionar un cliente');
            return;
        }

        if (empty($this->cart) || count(is_array($this->cart) ? $this->cart : $this->cart->toArray()) === 0) {
            $this->notifyUser('error', 'El carrito está vacío');
            return;
        }

        if (!$this->expected_delivery_date) {
            $this->notifyUser('error', 'Debes ingresar fecha de entrega esperada');
            return;
        }

        $previewSale = PreviewSale::create([
            'customer_id' => $this->customerId,
            'created_by_user_id' => auth()->id(),
            'expected_delivery_date' => $this->expected_delivery_date,
            'notes' => $this->notes,
            'status' => 'pending',
            'total' => $this->totalCart
        ]);

        $cartArray = is_array($this->cart) ? $this->cart : $this->cart->toArray();
        
        foreach ($cartArray as $item) {
            PreviewSaleDetail::create([
                'preview_sale_id' => $previewSale->id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'notes' => $item['notes'] ?? null
            ]);

            $product = Product::find($item['id']);
            $product->decrement('stock', $item['quantity']);
            $product->increment('stock_in_trial', $item['quantity']);
        }

        session()->forget("previewCart");
        $this->notifyUser('success', 'Preventa creada exitosamente');
        return redirect()->route('preview-sales.show', $previewSale);
    }
}
