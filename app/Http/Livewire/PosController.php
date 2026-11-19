<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Company;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use App\Models\Customer;
//use Barryvdh\DomPDF\PDF;
use App\Traits\JsonTrait;
use App\Models\SaleCredit;
use App\Models\SaleDetail;
use App\Models\Denomination;
use App\Models\SaleCreditPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\InvoiceNumberService;
use App\Services\InvoiceService;
use App\Services\ScaleBarcodeService;
use Illuminate\Support\Facades\Session;

class PosController extends Component
{

    use JsonTrait;


    public $posCart;
    public $totalCart = 0;
    public $totalItems = 0;
    public $total, $cash, $change, $customers = [], $customerId,
    $customerName, $status, $payment_type, $payment_method, $discount, $discount_total,
    $custom_due_date;
    public $showDiscountInput = [];
    // Cambia cuando se rechaza un valor del carrito para que el campo vuelva a mostrar el valor real
    public $cartInputsVersion = 0;
    public $itemsQuantity = 0;
    public $invoice_type_id = 1;
    public $lastSaleId;
    public $showWeightModal = false;
    public $weightModalProductId = null;
    public $weightModalProductName = '';
    public $weightModalPrice = 0;
    public $weightModalStock = 0;
    public $weightModalUnit = 'kg';
    public $weightModalWeight = null;
    public $showOutOfStockModal = false;
    public $outOfStockProductId = null;
    public $outOfStockProductName = '';
    public $outOfStockCurrentStock = 0;
    public $outOfStockAddQty = 0;
    public $outOfStockRequestedQty = 1;
    public $tempStockAdjustments = [];
    public $lowStockProducts = [];
    public $lowStockAlerted = [];

    

    

    //ESCUCHAS DE EVENTOS
    protected $listeners = [
        'scan-code' => 'addProduct',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'saveSale' => 'saveSale',
        'refresh' => '$refresh',
        'scan-code-byid' => 'scanCodeById',
        'setCustomer',
        'print-json',
        'openLowStockModal' => 'openLowStockModal',
        'openOutOfStockModal' => 'openOutOfStockModal',
        'updateStockFromNoStockModal' => 'updateStockFromNoStockModal',
        'print-last' => 'printLastSale', // atajo F6
    ];

    private function normalizeSalePaymentMethod($method)
    {
        $normalized = strtoupper(trim((string) $method));

        $aliases = [
            'TARJETA' => 'TDC',
            'TRANSFERENCIA' => 'TRANS',
            'TIGO_MONEY' => 'TIGO',
            'OTRO' => 'OTROS',
        ];

        $normalized = $aliases[$normalized] ?? $normalized;

        $allowed = ['EFECTIVO', 'TDD', 'TDC', 'TRANS', 'TIGO', 'CHEQUE', 'OTROS'];

        return in_array($normalized, $allowed, true) ? $normalized : null;
    }

    public function mount()
    {
        $this->cash = 0;
        $this->change = 0;
        $this->customers = Customer::orderBy('name', 'ASC')->get();
        $this->status = 'PAGADO';
        $this->payment_type = 'CONTADO';
        $this->payment_method = 'EFECTIVO';
        $this->discount = 0;
        $this->discount_total = 0;

        // Cliente elegido antes de recargar la página (se guarda en sesión como el carrito)
        $savedCustomer = session()->has('posCustomerId') ? Customer::find(session('posCustomerId')) : null;
        $previewCustomerId = session('preview_sale_customer_id');
        $this->assignOccasionalCustomer();
        if ($savedCustomer) {
            $this->setCustomer($savedCustomer->id, $savedCustomer->isDefaultOccasional() ? $savedCustomer->name : $savedCustomer->display_name);
        }

        // Cargar cliente si viene de preventa
        // Solo mientras la preventa siga pendiente de cobro
        if ($previewCustomerId && session()->has('preview_sale_id')) {
            $customer = Customer::find($previewCustomerId);
            if ($customer) {
                $this->customerId = $customer->id;
                $this->customerName = $customer->display_name;
                session(['preview_sale_customer_id' => $customer->id]);
            }
        }

        if (session()->has("posCart")) {
            $this->posCart = collect(session("posCart"));
        } else {
            $this->posCart = collect([]);
        }

        $this->tempStockAdjustments = [];
        session()->forget('posTempStockAdjustments');

        $this->total = $this->totalCart(); // Obtener el total del carrito
        $this->itemsQuantity = $this->totalItems(); // Obtener la cantidad total de ítems
        $this->loadLowStockProducts();

    }
    

    public function render()
    {
        $this->itemsQuantity = $this->totalItems(); // Obtener la cantidad total de ítems
        $this->total = $this->totalCart(); // Obtener el total del carrito

        return view('livewire.pos.component', [
            'denominations' => Denomination::get(),
            'posCart' => $this->posCart,
            'sales' => Sale::latest()->take(5)->get(), // Solo las 5 más recientes
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function getSales()
    {
        $this->emit('update-sales', Sale::latest()->take(10)->get());
    }

    //FUNCION PARA IMPRIMIR LAS VENTAS
    public function printSale($sale_id)
    {
        $b64 = $this->jsonData2($sale_id);
        $this->dispatchBrowserEvent('print-json', ['data' => $b64]);
    }

    //ASIGNAR CLIENTES OCACIONAL DE FORMA AUTOMATICA
    public function assignOccasionalCustomer()
    {
        $occasionalCustomerId = Customer::DEFAULT_OCCASIONAL_ID;
        $occasionalCustomer = Customer::find($occasionalCustomerId);

        // También el cliente de preventa: si quedaba en sesión, volvía a aparecer al recargar el POS
        session()->forget(['posCustomerId', 'preview_sale_customer_id']);

        if ($occasionalCustomer) {
            $this->customerId = $occasionalCustomer->id;
            $this->customerName = $occasionalCustomer->name;
        } else {
            $this->customerId = null; // O asigna un valor predeterminado
            $this->customerName = "Cliente no definido";
            $this->emit('sale-error', 'Cliente ocasional no encontrado en la base de datos');
        }
    }

        public function addProduct($barcode, $quantity = 1)
    {
        $barcode = trim((string) $barcode);

        // 1) Un producto con este código exacto siempre tiene prioridad
        //    (productos que no pasan por la balanza, códigos importados, códigos cortos)
        if (Product::where('barcode', $barcode)->exists()) {
            $this->addUnitProduct($barcode, $quantity);
            return;
        }

        // 2) Etiqueta de balanza (formato en Configuración → Balanza)
        $label = app(ScaleBarcodeService::class)->parse($barcode);

        if ($label) {
            if ($label['error']) {
                // Si el producto no existe se ofrece registrarlo; si existe, el problema es de configuración
                $this->emit($label['product'] ? 'sale-error' : 'scan-notfound', $label['error']);
                return;
            }

            if ($label['type'] === 'weight') {
                $this->addWeightProduct($label['product'], $label['value']);
            } else {
                $this->addUnitProduct($label['product']->barcode, (int) $label['value']);
            }
            return;
        }

        // 3) Ni producto ni etiqueta de balanza
        $this->addUnitProduct($barcode, $quantity);
    }

    private function addWeightProduct(Product $product, $weight)
    {
        if ($product->stock < $weight) {
            $this->emit('no-stock', 'Stock insuficiente para "' . $product->name . '".');
            return;
        }

        $pricePerKg = round($product->price_per_kg, 2);
        $subtotal = round($weight * $pricePerKg, 2);

        $exist = $this->posCart->firstWhere('id', $product->id);

        if ($exist) {
            $exist['quantity'] = round((float) $exist['quantity'] + $weight, 3);
            $exist['total'] = round($exist['quantity'] * $product->price_per_kg, 2);
            $exist['w'] = true;
            $this->moveCartItemToTop($exist);
        } else {
            $this->posCart->prepend([
                'id' => $product->id,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'quantity' => $weight,
                'price' => $pricePerKg,
                'total' => $subtotal,
                'w' => $product->is_weighted
            ]);
        }

        $previousQty = (float) ($exist['quantity'] ?? 0);
        $this->updateCart();
        $this->emit('scan-ok', $exist ? 'Cantidad actualizada' : 'Producto agregado al carrito por peso.');
        $this->emitLowStockIfNeeded($product->id, $previousQty);
    }



    public function addUnitProduct($barcode, $quantity = 1)
    {
        $product = Product::where('barcode', $barcode)->first();
    
        if (!$product) {
            $this->emit('scan-notfound', 'El producto no está registrado');
            return;
        }

        // Si el producto es por peso, usar siempre el modal manual (kg/g)
        if ((bool) $product->is_weighted) {
            $this->openWeightModal($product->id);
            return;
        }

        if ($this->getEffectiveStock($product) <= 0) {
            $this->emitOutOfStockPrompt($product, $quantity, 'addUnit');
            return;
        }
    
        if ($this->getEffectiveStock($product) < $quantity) {
            $this->emitOutOfStockPrompt($product, $quantity, 'addUnit');
            return;
        }
    
        $exist = $this->posCart->firstWhere('id', $product->id);
    
        if ($exist) {
            // Si ya existe, aumenta la cantidad
            $this->increaseQty($product->id, $quantity);
            return;
        }
        
    
        // Agrega un nuevo producto al carrito
        $this->posCart->prepend([
            'id' => $product->id,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'quantity' => $quantity,
            'price' => $product->price,
            'total' => $product->price * $quantity,
            'w' => $product->is_weighted
        ]);
    
        $this->updateCart();
        $this->emit('scan-ok', 'Producto agregado');
        $this->emitLowStockIfNeeded($product->id, 0);
    }

    public function openWeightModal($productRef)
    {
        $product = is_numeric($productRef)
            ? Product::find((int) $productRef)
            : Product::where('barcode', (string) $productRef)->first();

        if (!$product || !$product->is_weighted) {
            $this->emit('sale-error', 'Producto no valido para carga manual por peso.');
            return;
        }

        $this->weightModalProductId = $product->id;
        $this->weightModalProductName = (string) $product->name;
        $this->weightModalPrice = round((float) ($product->price_per_kg ?: $product->price), 2);
        $this->weightModalStock = round((float) $product->stock, 3);
        $this->weightModalUnit = 'kg';
        $this->weightModalWeight = null;
        $this->showWeightModal = true;
        // Cerrar cualquier modal Bootstrap abierto antes de mostrar el modal de peso
        $this->dispatchBrowserEvent('close-all-modals-before-weight');
        $this->dispatchBrowserEvent('focus-manual-weight-input');
    }

    public function closeWeightModal()
    {
        $this->showWeightModal = false;
        $this->weightModalProductId = null;
        $this->weightModalProductName = '';
        $this->weightModalPrice = 0;
        $this->weightModalStock = 0;
        $this->weightModalUnit = 'kg';
        $this->weightModalWeight = null;
    }

    private function emitOutOfStockPrompt(Product $product, $requestedQty = 1, $retryAction = 'addUnit', $targetQty = null): void
    {
        $this->emit('no-stock', [
            'mode' => 'restock',
            'productId' => (int) $product->id,
            'productName' => (string) $product->name,
            'availableStock' => $this->getEffectiveStock($product),
            'requestedQty' => max(1, (int) $requestedQty),
            'retryAction' => (string) $retryAction,
            'targetQty' => is_null($targetQty) ? null : (int) $targetQty,
        ]);
    }

    public function updateStockFromNoStockModal($productId, $addQty, $requestedQty = 1, $retryAction = 'addUnit', $targetQty = null)
    {
        $product = Product::find((int) $productId);

        if (!$product) {
            $this->emit('sale-error', 'Producto no encontrado para actualizar stock.');
            return;
        }

        if (!is_numeric($addQty) || (int) $addQty <= 0) {
            $this->emit('sale-error', 'Cantidad inválida para actualizar stock.');
            return;
        }

        $addQty = (int) $addQty;
        $requestedQty = max(1, (int) $requestedQty);

        $product->increment('stock', $addQty);
        $product->refresh();

        $this->emit('scan-ok', 'Stock actualizado correctamente.');
        $this->loadLowStockProducts();

        if ($retryAction === 'setCartQty' && is_numeric($targetQty) && (int) $targetQty > 0) {
            $this->updateQuantity((int) $product->id, (int) $targetQty);
            return;
        }

        if ($retryAction === 'addUnit') {
            $availableQty = max(0, (int) floor($this->getEffectiveStock($product)));
            $qtyToAddInCart = min($requestedQty, $availableQty);

            if ($qtyToAddInCart > 0) {
                $this->addUnitProduct($product->barcode, $qtyToAddInCart);
            }
        }
    }

    public function openOutOfStockModal($productId, $requestedQty = 1)
    {
        $product = Product::find((int) $productId);

        if (!$product) {
            $this->emit('sale-error', 'Producto no encontrado para actualizar stock.');
            return;
        }

        $this->outOfStockProductId = (int) $product->id;
        $this->outOfStockProductName = (string) $product->name;
        $this->outOfStockCurrentStock = $this->getEffectiveStock($product);
        $this->outOfStockRequestedQty = max(1, (int) $requestedQty);
        $this->outOfStockAddQty = 0;
        $this->showOutOfStockModal = true;

        $this->dispatchBrowserEvent('focus-out-stock-input');
    }

    public function closeOutOfStockModal()
    {
        $this->showOutOfStockModal = false;
        $this->outOfStockProductId = null;
        $this->outOfStockProductName = '';
        $this->outOfStockCurrentStock = 0;
        $this->outOfStockAddQty = 0;
        $this->outOfStockRequestedQty = 1;
    }

    public function updatedOutOfStockAddQty($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_numeric($value)) {
            $this->outOfStockAddQty = 1;
            return;
        }

        $this->outOfStockAddQty = max(1, (int) $value);
    }

    public function confirmOutOfStockUpdate()
    {
        if (!$this->outOfStockProductId) {
            $this->emit('sale-error', 'No hay producto seleccionado para actualizar stock.');
            return;
        }

        $productId = (int) $this->outOfStockProductId;
        $addQty = max(1, (int) $this->outOfStockAddQty);
        $requestedQty = max(1, (int) $this->outOfStockRequestedQty);
        $this->closeOutOfStockModal();
        $this->updateStockFromNoStockModal($productId, $addQty, $requestedQty, 'addUnit');
    }

    public function updatedWeightModalWeight($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $normalized = str_replace(',', '.', (string) $value);

        if (!is_numeric($normalized)) {
            $this->weightModalWeight = null;
            return;
        }

        $number = (float) $normalized;

        if ($this->weightModalUnit === 'g') {
            $number = round($number);
            $maxInGrams = (int) round($this->weightModalStock * 1000);

            if ($maxInGrams > 0 && $number > $maxInGrams) {
                $number = $maxInGrams;
            }

            $this->weightModalWeight = (string) max($number, 0);
            return;
        }

        $number = round($number, 3);
        if ($this->weightModalStock > 0 && $number > $this->weightModalStock) {
            $number = $this->weightModalStock;
        }

        $this->weightModalWeight = rtrim(rtrim(number_format(max($number, 0), 3, '.', ''), '0'), '.');
    }

    private function weightModalInputToKg(): float
    {
        $weightInput = str_replace(',', '.', (string) $this->weightModalWeight);

        if (!is_numeric($weightInput)) {
            return 0;
        }

        $weightNumber = (float) $weightInput;

        if ($weightNumber <= 0) {
            return 0;
        }

        if ($this->weightModalUnit === 'g') {
            return round($weightNumber / 1000, 3);
        }

        return round($weightNumber, 3);
    }

    public function setWeightModalUnit($unit)
    {
        if (!in_array($unit, ['kg', 'g'], true)) {
            return;
        }

        $currentUnit = $this->weightModalUnit;

        if ($currentUnit === $unit) {
            return;
        }

        $weightInput = str_replace(',', '.', (string) $this->weightModalWeight);

        if (is_numeric($weightInput) && (float) $weightInput > 0) {
            $weight = (float) $weightInput;

            if ($currentUnit === 'kg' && $unit === 'g') {
                $this->weightModalWeight = (string) ((int) round($weight * 1000));
            }

            if ($currentUnit === 'g' && $unit === 'kg') {
                $kgValue = round($weight / 1000, 3);
                $this->weightModalWeight = rtrim(rtrim(number_format($kgValue, 3, '.', ''), '0'), '.');
            }
        }

        $this->weightModalUnit = $unit;
        $this->dispatchBrowserEvent('focus-manual-weight-input');
    }

    public function confirmWeightProduct()
    {
        if (!$this->weightModalProductId) {
            $this->emit('sale-error', 'No hay producto seleccionado para ingresar peso.');
            return;
        }

        $product = Product::find($this->weightModalProductId);

        if (!$product || !$product->is_weighted) {
            $this->emit('sale-error', 'Producto no valido para ventas por peso.');
            return;
        }

        $weightInKg = $this->weightModalInputToKg();

        // En gramos no permitimos fracciones para evitar cargas ambiguas.
        if ($this->weightModalUnit === 'g' && fmod((float) $this->weightModalWeight, 1.0) !== 0.0) {
            $this->emit('sale-error', 'En gramos, ingrese un valor entero.');
            return;
        }

        if ($weightInKg <= 0) {
            $this->emit('sale-error', 'Ingrese un peso valido mayor a 0.');
            return;
        }

        if ($product->stock < $weightInKg) {
            $this->emit('no-stock', 'Stock insuficiente para "' . $product->name . '".');
            return;
        }

        $pricePerKg = round((float) ($product->price_per_kg ?: $product->price), 2);
        $subtotal = round($weightInKg * $pricePerKg, 2);
        $exist = $this->posCart->firstWhere('id', $product->id);

        if ($exist) {
            $exist['quantity'] = round((float) $exist['quantity'] + $weightInKg, 3);
            $exist['price'] = $pricePerKg;
            $exist['total'] = round($exist['quantity'] * $pricePerKg, 2);
            $exist['w'] = true;
            $this->moveCartItemToTop($exist);
        } else {
            $this->posCart->prepend([
                'id' => $product->id,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'quantity' => $weightInKg,
                'price' => $pricePerKg,
                'total' => $subtotal,
                'w' => true,
            ]);
        }

        $previousQty = (float) ($exist['quantity'] ?? 0);
        $this->updateCart();
        $this->closeWeightModal();
        $this->emit('scan-ok', $exist ? 'Cantidad actualizada' : 'Producto agregado al carrito por peso.');
        $this->emitLowStockIfNeeded($product->id, $previousQty);
    }

    private function emitLowStockIfNeeded($productId, $previousCartQty = null)
    {
        $product = Product::find($productId);

        if (!$product || (float) $product->min_stock <= 0) {
            return;
        }

        // Suma robusta para evitar diferencias de tipo entre IDs (string/int)
        $cartQty = (float) $this->posCart->sum(function ($item) use ($productId) {
            $itemId = (int) ($item['id'] ?? 0);
            if ($itemId !== (int) $productId) {
                return 0;
            }

            return (float) ($item['quantity'] ?? 0);
        });

        $remaining = round((float) $product->stock - $cartQty, 3);
        $minStock = (float) $product->min_stock;
        $isWeighted = (bool) ($product->is_weighted ?? false);
        $previousQty = (float) ($previousCartQty ?? max($cartQty - 1, 0));
        $beforeRemaining = round((float) $product->stock - $previousQty, 3);

        // Regla: avisar solo cuando el stock restante cruza el umbral minimo
        // (de estar por encima a quedar en minimo o por debajo), tanto en unidad como en peso.
        $shouldAlert = ($beforeRemaining > $minStock && $remaining <= $minStock);

        if ($shouldAlert) {
            if ($isWeighted && in_array((int) $productId, $this->lowStockAlerted, true)) {
                return;
            }

            $message = $isWeighted
                ? 'Stock bajo en "' . $product->name . '". Restante: ' . $remaining . ' | Minimo: ' . $product->min_stock
                : 'Stock minimo alcanzado en "' . $product->name . '". Restante: ' . $remaining . ' | Minimo: ' . $product->min_stock;
            $this->emit('low-stock', $message);
            $this->loadLowStockProducts();
            $this->lowStockAlerted[] = (int) $productId;
            return;
        }

        if ($remaining > $minStock) {
            $this->lowStockAlerted = array_values(array_filter(
                $this->lowStockAlerted,
                fn($id) => (int) $id !== (int) $productId
            ));
        }
    }

    public function openLowStockModal()
    {
        $this->lowStockProducts = $this->getLowStockProducts()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'barcode' => $p->barcode,
                    'stock' => (float) $p->stock,
                    'min_stock' => (float) $p->min_stock,
                    'is_weighted' => (bool) $p->is_weighted,
                ];
            })->values()->toArray();

        $this->emit('show-low-stock-modal');
    }

    private function getLowStockProducts()
    {
        return Product::query()
            ->whereNotNull('min_stock')
            ->where('min_stock', '>', 0)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock', 'asc')
            ->get(['id', 'name', 'barcode', 'stock', 'min_stock', 'is_weighted']);
    }

    public function loadLowStockProducts()
    {
        $products = $this->getLowStockProducts();

        $this->lowStockProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'stock' => (float) $product->stock,
                'min_stock' => (float) $product->min_stock,
                'is_weighted' => (bool) $product->is_weighted,
            ];
        })->values()->toArray();
    }

    private function moveCartItemToTop(array $updatedItem): void
    {
        $targetId = (int) ($updatedItem['id'] ?? 0);

        $remainingItems = $this->posCart
            ->reject(fn($item) => (int) ($item['id'] ?? 0) === $targetId)
            ->values();

        $this->posCart = collect([$updatedItem])->merge($remainingItems)->values();
    }

    private function getEffectiveStock(Product $product): float
    {
        return (float) $product->stock;
    }

    public function exportLowStockPdf()
    {
        $products = Product::query()
            ->whereNotNull('min_stock')
            ->where('min_stock', '>', 0)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock', 'asc')
            ->get(['name', 'barcode', 'stock', 'min_stock']);

        $pdf = Pdf::loadView('pdf.low-stock-a4', [
            'products' => $products,
            'company' => Company::first(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'stock-bajo-' . now()->format('Ymd_His') . '.pdf');
    }

   public function updateCart()
    {
        session(['posCart' => $this->posCart]);

        $this->totalCart = round($this->posCart->sum(function ($item) {
            $precio = floatval($item['price']);
            $descuento = isset($item['manual_discount']) ? floatval($item['manual_discount']) : 0;
            $precioFinal = max($precio - $descuento, 0);
            return $precioFinal * $item['quantity'];
        }), 2);

        $this->totalItems = $this->posCart->sum('quantity');

        // Mantener coherencia con saveSale()
        $this->total = $this->totalCart;

        // Recalcular cambio si aplica
        $this->change = $this->cash - $this->total;
    }


    

    public function inCart($product_id)
    {
        return $this->posCart->where('id', $product_id)->count() > 0;
    }

    public function updateQuantity($productId, $quantity)
    {
        if (!is_numeric($quantity)) {
            $this->emit('sale-error', 'EL VALOR DE LA CANTIDAD ES INCORRECTO');
            return;
        }
        $item = $this->posCart->firstWhere('id', $productId);

        if (!$item) {
            $this->emit('sale-error', 'Producto no encontrado en el carrito');
            return;
        }

        if (!isset($item['price']) || !is_numeric($item['price'])) {
            $this->emit('sale-error', 'Carrito corrupto (precio inválido)');
            return;
        }

        $product = Product::find($productId);

        if (!$product) {
            $this->emit('sale-error', 'Producto no existe en la BD.');
            return;
        }

        $isWeighted = (bool) ($item['w'] ?? $product->is_weighted ?? false);

        if ((float) $quantity < 0) {
            $this->emit('sale-error', 'LA CANTIDAD NO PUEDE SER NEGATIVA');
            $this->cartInputsVersion++;
            return;
        }

        // Un producto por unidad no admite fracciones (antes 2,5 se cambiaba a 2 sin avisar)
        if (!$isWeighted && floor((float) $quantity) != (float) $quantity) {
            $this->emit('sale-error', '"' . $product->name . '" SE VENDE POR UNIDAD: INGRESA UNA CANTIDAD ENTERA');
            $this->cartInputsVersion++;
            return;
        }

        $quantity = $isWeighted ? round((float) $quantity, 3) : intval($quantity);

        if ($quantity <= 0) {
            $this->removeItem($productId);
            $this->updateCart();
            $this->emit('scan-ok', 'Producto quitado del carrito');
            return;
        }

        if ($quantity > $this->getEffectiveStock($product)) {
            if ($this->getEffectiveStock($product) <= 0) {
                $this->emitOutOfStockPrompt($product, $quantity, 'setCartQty', $quantity);
                return;
            }

            $this->emitOutOfStockPrompt($product, $quantity, 'setCartQty', $quantity);
            return;
        }

        $previousQty = (float) ($item['quantity'] ?? 0);

        // Actualizar item y colocarlo al inicio
        $item['quantity'] = $quantity;
        $item['total'] = $item['price'] * $quantity;
        $this->moveCartItemToTop($item);

        $this->updateCart();
        $this->emit('scan-ok', 'Cantidad actualizada');
        $this->emitLowStockIfNeeded($product->id, $previousQty);
    }


    public function removeItem($product_id)
    {
        // Validar ID
        if (!$product_id || !is_numeric($product_id)) {
            $this->emit('sale-error', 'ID de producto inválido');
            return;
        }

        // Validar existencia en el carrito
        $item = $this->posCart->firstWhere('id', $product_id);

        if (!$item) {
            $this->emit('sale-error', 'El producto no está en el carrito');
            return;
        }

        // Eliminar producto del carrito
        $this->posCart = $this->posCart->reject(
            fn($i) => $i['id'] == $product_id
        );

        // Recalcular todo
        $this->updateCart();

        $this->emit('no-stock', 'Producto eliminado');
    }



    public function clearCart()
    {
        // Reiniciar carrito y elementos UI
        $this->posCart = collect([]);
        $this->showDiscountInput = [];

        // Reiniciar totales y efectivo
        $this->totalCart = 0;
        $this->totalItems = 0;
        $this->total = 0;
        $this->cash = 0;
        $this->change = 0;
        $this->lowStockAlerted = [];
        $this->tempStockAdjustments = [];

        // Guardar
        $this->save();

        $this->emit('scan-ok', 'Carrito vaciado');
    }





    public function save()
    {
        // Integridad: antes de guardar, aseguramos que el carrito es válido
        foreach ($this->posCart as $item) {
            if (!isset($item['id'], $item['price'], $item['quantity'])) {
                $this->emit('sale-error', 'Error al guardar: carrito corrupto');
                return;
            }

            if (!is_numeric($item['price']) || !is_numeric($item['quantity'])) {
                $this->emit('sale-error', 'Error al guardar: datos inválidos en el carrito');
                return;
            }
        }

        session()->put('posCart', $this->posCart);
        session()->save();
    }


    public function totalCart()
    {
        if ($this->posCart === null) {
            $this->posCart = collect([]);
        }
    
        return round($this->posCart->sum(function ($item) {
            $precioBase = floatval($item['price']);
            $descuento = isset($item['manual_discount']) ? floatval($item['manual_discount']) : 0;
            $precioFinal = max($precioBase - $descuento, 0);
            return round($precioFinal * floatval($item['quantity']), 2);
        }), 2);
    }
    

    public function totalItems()
    {
        if ($this->posCart === null) {
            $this->posCart = collect([]);
        }

        return $this->posCart->count('quantity');
        //return $this->posCart->sum('quantity');
    }

    // GUARDAR VENTAS 
    public function saveSale()
    {
        /*
        * =========================
        * VALIDACIONES PREVIAS
        * =========================
        */

        // 1) Validar carrito
        if (empty($this->posCart) || count($this->posCart) === 0) {
            $this->emit('sale-error', 'AGREGA PRODUCTOS A LA VENTA');
            return;
        }

        // 2) Validar total > 0
        if ($this->total <= 0) {
            $this->emit('sale-error', 'AGREGA PRODUCTOS A LA VENTA');
            return;
        }

        // 3) Validar efectivo numérico y no negativo
        if (!is_numeric($this->cash)) {
            $this->emit('sale-error', 'INGRESA UN MONTO VÁLIDO DE EFECTIVO');
            return;
        }

        if ($this->cash < 0) {
            $this->emit('sale-error', 'EL EFECTIVO NO PUEDE SER NEGATIVO');
            return;
        }

        // 4) Reglas específicas de CONTADO
        if ($this->payment_type == 'CONTADO') {
            if ($this->total > $this->cash) {
                $this->emit('sale-error', 'EL EFECTIVO ES MENOR AL TOTAL');
                return;
            }
        }

        // 5) Reglas específicas de CREDITO
        if ($this->payment_type === 'CREDITO') {

            // No permitir que crédito tenga cash >= total
            if ($this->cash >= $this->total) {
                $this->emit('sale-error', 'El monto ingresado no puede ser igual o mayor al total si la venta es a CRÉDITO');
                return;
            }

        }

        // 6) Bloquear sobrepago en métodos que no sean CONTADO (seguridad extra)
        if ($this->payment_type !== 'CONTADO' && $this->cash > $this->total) {
            $this->emit('sale-error', 'EL MONTO INGRESADO NO PUEDE SUPERAR EL TOTAL');
            return;
        }

        // 7) Validar cliente
        if ($this->customerId == null) {
            $this->emit('sale-error', 'DEBES SELECCIONAR UN CLIENTE');
            return;
        }

        $customer = Customer::find($this->customerId);

        if (!$customer) {
            $this->emit('sale-error', 'CLIENTE NO ENCONTRADO');
            return;
        }

        // 7.b) No permitir CRÉDITO si el cliente es ocasional/desconocido
        $customerNameNormalized = strtolower(trim($customer->name ?? ''));
        $occasionalNames = ['ocasional', 'desconocido'];
        $isOccasionalByName = in_array($customerNameNormalized, $occasionalNames, true);
        // Antes era un id fijo (6): bloqueaba a un cliente real con ese id y no al ocasional (id 1)
        $isOccasionalById = $customer->isDefaultOccasional();

        if ($this->payment_type === 'CREDITO' && ($isOccasionalByName || $isOccasionalById)) {
            $this->emit('sale-error', 'No se puede registrar una venta a crédito con cliente ocasional.');
            return;
        }

        // 8) Validar método de pago
        if (!$this->payment_method) {
            $this->emit('sale-error', 'DEBES SELECCIONAR UN MÉTODO DE PAGO');
            return;
        }

        $paymentMethod = $this->normalizeSalePaymentMethod($this->payment_method);

        if (!$paymentMethod) {
            $this->emit('sale-error', 'EL MÉTODO DE PAGO NO ES VÁLIDO');
            return;
        }

        // 9) Validar items de carrito (cantidad y precio)
        foreach ($this->posCart as $item) {

            if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price'])) {
                $this->emit('sale-error', 'Producto en el carrito con datos inválidos.');
                return;
            }

            if (!is_numeric($item['quantity']) || $item['quantity'] <= 0) {
                $this->emit('sale-error', 'Cantidad inválida en uno de los productos.');
                return;
            }

            if (!is_numeric($item['price']) || $item['price'] < 0) {
                $this->emit('sale-error', 'Precio inválido en uno de los productos.');
                return;
            }
        }

        // 10) Cargar productos del carrito una sola vez para validaciones y cálculo fiscal
        $productIds = collect($this->posCart)->pluck('id')->filter()->unique()->values();
        $productsMap = Product::with('category.vatType')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        // 11) Validar stock antes de procesar
        foreach ($this->posCart as $item) {
            $product = $productsMap->get((int) $item['id']);

            if (!$product) {
                $this->emit('sale-error', 'UNO DE LOS PRODUCTOS YA NO EXISTE EN EL SISTEMA');
                return;
            }

            if ($this->getEffectiveStock($product) < $item['quantity']) {
                $this->emit('sale-error', "Stock insuficiente para el producto: {$product->name}");
                return;
            }
        }

        /*
        * =========================
        * INICIO TRANSACCIÓN
        * =========================
        */

        DB::beginTransaction();

        try {

            $saleStatus = ($this->payment_type == 'CREDITO' && $this->total > $this->cash)
                ? 'PENDIENTE'
                : 'PAGADO';

            // 🔹 Cálculo de bases y IVA
            $totalExentas     = 0;
            $totalIva5        = 0;
            $totalIva10       = 0;
            $totalGravadas5   = 0;
            $totalGravadas10  = 0;

            foreach ($this->posCart as $item) {
                $product = $productsMap->get((int) $item['id']);
                if (!$product) continue;

                $taxRate = optional(optional($product->category)->vatType)->rate;
                if (is_null($taxRate)) {
                    $taxRate = $product->tax_rate ?? 10;
                }

                if (!in_array((int) $taxRate, [0, 5, 10], true)) {
                    $taxRate = 10;
                }

                $subtotal = $this->calcularPrecioFinal($item) * $item['quantity'];

                if ($taxRate == 5) {
                    $base = round($subtotal / 1.05, 0);
                    $iva  = round($subtotal - $base, 0);
                    $totalGravadas5 += $base;
                    $totalIva5      += $iva;
                } elseif ($taxRate == 10) {
                    $base = round($subtotal / 1.1, 0);
                    $iva  = round($subtotal - $base, 0);
                    $totalGravadas10 += $base;
                    $totalIva10      += $iva;
                } else {
                    $totalExentas += $subtotal;
                }
            }

            // 🔹 Crear la venta
            $sale = Sale::create([
                'total'             => round($this->total, 2),
                'items'             => collect($this->posCart)->sum('quantity'),
                'cash'              => round($this->cash, 2),
                'change'            => round($this->cash - $this->total, 2),
                'user_id'           => Auth::user()->id,
                'customer_id'       => $this->customerId,
                'status'            => $saleStatus,
                'payment_type'      => $this->payment_type,
                'payment_method'    => $paymentMethod,
                'discount'          => round($this->discount, 2),
                'discount_total'    => round($this->discount_total, 2),
                'total_exentas'     => round($totalExentas, 0),
                'total_iva5'        => round($totalIva5, 0),
                'total_iva10'       => round($totalIva10, 0),
                'total_gravadas_5'  => round($totalGravadas5, 0),
                'total_gravadas_10' => round($totalGravadas10, 0),
            ]);

            // Si viene de preventa, guardar relación y actualizar estado
            if (session()->has('preview_sale_id')) {
                $previewSaleId = session('preview_sale_id');
                $sale->update(['preview_sale_id' => $previewSaleId]);
                
                // Actualizar estado de la preventa a "confirmed"
                $previewSale = \App\Models\PreviewSale::find($previewSaleId);
                if ($previewSale) {
                    $previewSale->update([
                        'status' => 'confirmed',
                        'total_paid' => round($this->cash, 2)
                    ]);
                }
                
                session()->forget('preview_sale_id');
                session()->forget('preview_sale_customer_id');
            }

            // 🔹 Registrar crédito
            if ($this->payment_type == 'CREDITO') {

                $currentCredit = SaleCredit::where('customer_id', $this->customerId)
                    ->where('status', 'PENDIENTE')
                    ->sum('remaining_balance');

                $nuevoCredito = $this->total - $this->cash;

                if ((float) $customer->credit_limit <= 0) {
                    throw new \Exception('El cliente no tiene límite de crédito asignado. Cárgalo en Clientes.');
                }

                if (($currentCredit + $nuevoCredito) > $customer->credit_limit) {
                    $disponible = max(0, $customer->credit_limit - $currentCredit);
                    throw new \Exception('El cliente ha superado su límite de crédito. Disponible: Gs. ' . number_format($disponible, 0, ',', '.'));
                }

                $remainingBalance = max(0, round($this->total - $this->cash, 2));
                $creditStatus     = ($remainingBalance > 0) ? 'PENDIENTE' : 'PAGADO';
                $dueDate          = $this->custom_due_date ?? now()->addDays(30);

                $credit = SaleCredit::create([
                    'sale_id'           => $sale->id,
                    'customer_id'       => $this->customerId,
                    'total_credit'      => round($this->total, 2),
                    'amount_paid'       => round($this->cash, 2),
                    'remaining_balance' => $remainingBalance,
                    'status'            => $creditStatus,
                    'due_date'          => $dueDate
                ]);

                if ($this->cash > 0) {
                    SaleCreditPayment::create([
                        'credit_id'    => $credit->id,
                        'amount_paid'  => round(min($this->cash, $credit->total_credit), 2),
                        'payment_date' => now(),
                        'user_id'      => Auth::user()->id
                    ]);
                }
            }

            // 🔹 Guardar los detalles y actualizar stock
            foreach ($this->posCart as $item) {

                $product = $productsMap->get((int) $item['id']);
                if (!$product) continue;

                SaleDetail::create([
                    'price'                   => $item['price'],
                    'cost'                    => (float) $product->cost > 0 ? $product->cost : null, // costo al momento de la venta
                    'quantity'                => $item['quantity'],
                    'sub_total'               => $this->calcularPrecioFinal($item) * $item['quantity'],
                    'manual_price'            => $item['manual_price'] ?? null,
                    'manual_discount'         => $item['manual_discount'] ?? null,
                    'manual_discount_percent' => $item['manual_discount_percent'] ?? null,
                    'product_id'              => $item['id'],
                    'sale_id'                 => $sale->id
                ]);

                // Solo descontar stock si NO viene de una preventa (ya fue descontado allá)
                if (!isset($previewSale)) {
                    $product->decrement('stock', $item['quantity']);
                }
            }

            // 🔹 Factura legal
            if ($this->invoice_type_id == 2) {

                $company = Company::first();
                if (!$company) {
                    throw new \Exception('No se encontró la empresa emisora.');
                }

                $invoiceNumber = \App\Services\InvoiceNumberService::generate($company);

                $sale->update([
                    'company_id'      => $company->id,
                    'invoice_type_id' => 2,
                    'invoice_number'  => $invoiceNumber,
                ]);
            }

            DB::commit();

            // Venta guardada: el cliente vuelve siempre a Ocasional (sea ocasional o con RUC).
            // Va justo tras el commit para que un error al imprimir no deje el cliente anterior.
            // Si falla antes (p. ej. límite de crédito) el cliente elegido se mantiene.
            $this->assignOccasionalCustomer();

            // 🔹 Acciones posteriores
            $this->completeSale();
            $this->lastSaleId = $sale->id;

            // Qué hacer al terminar la venta: Configuración → Impresión (ask | ticket | none)
            $printMode = optional(Company::current())->print_after_sale ?? 'ask';

            if ($sale->invoice_type_id == 2) {
                $this->emit('show-print-options', base64_encode($sale->id));
                $this->emit('print-legal-invoice', $sale->id);
            } elseif ($printMode === 'ticket') {
                $this->emit('venta-registrada', base64_encode($sale->id));
            } elseif ($printMode !== 'none') {
                $this->emit('show-print-options', base64_encode($sale->id));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->emit('sale-error', $e->getMessage());
        }

        $this->getSales();
    }





    public function completeSale()
    {
        // Limpiar carrito
        $this->clearCart();
        $this->cash = 0;
        $this->change = 0;
        $this->total = $this->totalCart();
        $this->itemsQuantity = $this->totalItems();
        $this->payment_type = 'CONTADO'; // Restablecer a 'CONTADO'
        $this->payment_method = 'EFECTIVO'; // Restablecer método de pago por defecto
        $this->showDiscountInput = [];
        $this->emit('sale-ok', 'Venta realizada');
       
    }

    // ==========================================
    // Métodos de impresión post venta
    // ==========================================

    public function printTicket($saleHash)
    {
        $saleId = base64_decode($saleHash);
        $sale = Sale::find($saleId);

        if (!$sale) {
            $this->emit('sale-error', 'Venta no encontrada.');
            return;
        }

        // Redirigir o emitir evento para abrir ticket
        $this->emit('venta-registrada', base64_encode($sale->id));
        $this->dispatchBrowserEvent('close-modal', ['modal' => 'printOptionsModal']);
    }

    // F6: reimprime el último ticket del usuario conectado
    public function printLastSale()
    {
        $sale = Sale::where('user_id', auth()->id())->latest('id')->first();

        if (!$sale) {
            $this->emit('sale-error', 'No tienes ventas para reimprimir.');
            return;
        }

        $this->emit('venta-registrada', base64_encode($sale->id));
    }

   // 🧾 FACTURA LEGAL (con detalle)
    public function printFacturaLegal($saleHash)
    {
        $saleId = base64_decode($saleHash);
        $sale = Sale::with('customer')->find($saleId);

        if (!$sale) {
            $this->emit('sale-error', 'Venta no encontrada.');
            return;
        }

        // Validar que no sea cliente ocasional usando el cliente de la venta
        $customerNameNormalized = strtolower(trim($sale->customer->name ?? ''));
        if ($customerNameNormalized === 'ocasional' || $sale->customer_id == null) {
            $this->emit('sale-error', 'No puedes generar factura legal para clientes ocasionales.');
            return;
        }

        $saleId = base64_decode($saleHash);
        $sale = Sale::find($saleId);

        if (!$sale) {
            $this->emit('sale-error', 'Venta no encontrada.');
            return;
        }

        // ✅ Generar número legal si aún no existe
        if (!$sale->invoice_number) {
            $company = Company::first();
            $invoiceNumber = InvoiceNumberService::generate($company);

            $sale->company_id = $company->id;
            $sale->invoice_type_id = 2; // Tipo Factura Legal
            $sale->invoice_number = $invoiceNumber;
            $sale->save();
        }

        // ✅ Emitir evento al navegador para abrir el PDF
        $url = route('invoice.legal', ['hash' => base64_encode($sale->id)]);
        $this->dispatchBrowserEvent('open-print-url', ['url' => $url]);
        $this->dispatchBrowserEvent('close-modal', ['modal' => 'printOptionsModal']);
    }


    // 🔧 GENERA FACTURA LEGAL (vista imprimible en navegador)
    public function generateInvoicePDF($hash)
    {
        [$sale, $company] = InvoiceService::sale(base64_decode($hash));

        return view('pdf.invoice.invoice_legal', [
            'sale' => $sale,
            'company' => $company,
            'auto_print' => true,
        ]);
    }




    public function setCustomer($customerId, $customerName)
    {
        $this->customerId = $customerId;
        $this->customerName = $customerName;

        // Igual que el carrito: sobrevive a una recarga o a ir a registrar un producto
        session(['posCustomerId' => $customerId]);
    }

   public function ACash($value)
    {
        // Bloqueo total si es crédito
        if ($this->payment_type === 'CREDITO') {
            return;
        }

        if (!is_numeric($value)) {
            return;
        }

        // Exacto = total
        if ($value == 0) {
            $this->cash = $this->total;
        } else {
            $this->cash += $value;
        }

        // Evítar efectivo negativo
        if ($this->cash < 0) {
            $this->cash = 0;
        }

        // Calcular cambio
        $this->change = $this->cash - $this->total;
    }



    public function resetCustomer()
    {
        $this->customerId = null;
        $this->customerName = null;
    }

    public function scanCodeById($productId)
    {
        $this->increaseQty($productId);
    }

    // =======================
    // 🔸 DESCUENTOS MANUALES
    // =======================

    public function updateManualDiscount($productId, $value)
    {
        $item = $this->posCart->firstWhere('id', $productId);
        if (!$item) {
            $this->emit('sale-error', 'Producto no encontrado en el carrito');
            return;
        }

        $value = trim((string) $value) === '' ? 0 : $value;

        if (!is_numeric($value) || $value < 0) {
            $this->emit('sale-error', 'EL DESCUENTO DEBE SER UN NÚMERO MAYOR O IGUAL A 0');
            $this->cartInputsVersion++;
            return;
        }

        // Evita que un error de tipeo (ej: 50000 en vez de 5000) deje el producto gratis
        if ($value >= (float) $item['price']) {
            $this->emit('sale-error', 'EL DESCUENTO (' . number_format($value, 0, ',', '.') . ') DEBE SER MENOR AL PRECIO (' . number_format($item['price'], 0, ',', '.') . ')');
            $this->cartInputsVersion++;
            return;
        }

        $this->posCart = $this->posCart->map(function ($item) use ($productId, $value) {
            if ((int) $item['id'] === (int) $productId) {
                $item['manual_discount'] = floatval($value);
                $precioFinal = max($item['price'] - floatval($value), 0);
                $item['total'] = $precioFinal * $item['quantity'];
            }
            return $item;
        });
    
        $this->updateCart();
        $this->emit('scan-ok', 'Descuento aplicado');
    }    

    private function calcularPrecioFinal($item)
    {
        $precioBase = floatval($item['price']);

        if (isset($item['manual_price'])) {
            return floatval($item['manual_price']);
        }

        if (isset($item['manual_discount'])) {
            return max($precioBase - floatval($item['manual_discount']), 0);
        }

        if (isset($item['manual_discount_percent'])) {
            $descuento = $precioBase * (floatval($item['manual_discount_percent']) / 100);
            return max($precioBase - $descuento, 0);
        }

        return $precioBase;
    }


      public function increaseQty($productId, $cant = 1)
    {
        if (!$productId || !is_numeric($cant) || $cant <= 0) {
            $this->emit('sale-error', 'Cantidad inválida.');
            return;
        }

        $product = Product::find($productId);

        if (!$product) {
            $this->emit('sale-error', 'Producto no encontrado');
            return;
        }

        // Para productos por peso, siempre abrir modal manual
        if ((bool) $product->is_weighted) {
            $this->openWeightModal($product->id);
            return;
        }

        if ($this->getEffectiveStock($product) <= 0) {
            $this->emitOutOfStockPrompt($product, $cant, 'addUnit');
            return;
        }

        $exist = $this->posCart->firstWhere('id', $productId);

        if ($exist) {

            // Validar integridad del item
            if (!isset($exist['quantity']) || !is_numeric($exist['quantity'])) {
                $this->emit('sale-error', 'Carrito corrupto (cantidad inválida)');
                return;
            }

            // Validar stock antes de incrementar
            if ($this->getEffectiveStock($product) < ($exist['quantity'] + $cant)) {
                $this->emitOutOfStockPrompt($product, $cant, 'addUnit');
                return;
            }

            // Actualizar item existente y moverlo al inicio
            $exist['quantity'] += $cant;
            $exist['total'] = round($exist['quantity'] * $exist['price'], 2);
            $this->moveCartItemToTop($exist);

        } else {

            // Producto nuevo en el carrito
            if ($this->getEffectiveStock($product) < $cant) {
                $this->emitOutOfStockPrompt($product, $cant, 'addUnit');
                return;
            }

            $this->posCart->prepend([
                'id' => $product->id,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'quantity' => $cant,
                'price' => $product->price,
                'total' => round($product->price * $cant, 2),
            ]);
        }

        $previousQty = (float) ($exist['quantity'] ?? 0);
        $this->updateCart();
        $this->emit('scan-ok', $exist ? 'Cantidad actualizada' : 'Producto agregado');
        $this->emitLowStockIfNeeded($product->id, $previousQty);
    }



    public function decreaseQty($productId)
    {
        // Buscar el producto en el carrito
        $item = $this->posCart->firstWhere('id', $productId);

        // Validaciones de integridad
        if (!$item) {
            $this->emit('scan-notfound', 'El producto no está en el carrito');
            return;
        }

        if (!is_array($item)) {
            $this->emit('sale-error', 'Carrito corrupto (item no es array)');
            return;
        }

        if (!isset($item['quantity']) || !is_numeric($item['quantity'])) {
            $this->emit('sale-error', 'Cantidad inválida en el carrito');
            return;
        }

        // Eliminar el producto temporalmente
        $this->posCart = $this->posCart->reject(function ($cartItem) use ($productId) {
            return $cartItem['id'] === $productId;
        });

        // Calcular nueva cantidad
        $newQty = $item['quantity'] - 1;

        // Agregarlo si queda cantidad positiva
        if ($newQty > 0) {
            $item['quantity'] = $newQty;
            $item['total'] = $item['price'] * $newQty;
            $this->posCart->prepend($item);
        }

        // Actualizar totales
        $this->updateCart();

        $this->emit('scan-ok', 'Cantidad actualizada');
    }



    public function toggleDiscountInput($productId)
    {
        $this->showDiscountInput[$productId] = !($this->showDiscountInput[$productId] ?? false);
    }



    // 📄 FACTURA SIN DETALLE (mismo número, omite ítems)
    public function printSummaryInvoice($saleHash)
    {
        $saleId = base64_decode($saleHash);
        $sale = Sale::with('customer')->find($saleId);

        if (!$sale) {
            $this->emit('sale-error', 'Venta no encontrada.');
            return;
        }

        // Validar que no sea cliente ocasional usando el cliente de la venta
        $customerNameNormalized = strtolower(trim($sale->customer->name ?? ''));
        if ($customerNameNormalized === 'ocasional' || $sale->customer_id == null) {
            $this->emit('sale-error', 'No puedes generar factura legal para clientes ocasionales.');
            return;
        }

        $saleId = base64_decode($saleHash);
        $sale = Sale::find($saleId);

        if (!$sale) {
            $this->emit('sale-error', 'Venta no encontrada.');
            return;
        }

        // ✅ Generar número legal si aún no existe
        if (!$sale->invoice_number) {
            $company = Company::first();
            $invoiceNumber = InvoiceNumberService::generate($company);

            $sale->company_id = $company->id;
            $sale->invoice_type_id = 2; // Tipo Factura Legal
            $sale->invoice_number = $invoiceNumber;
            $sale->save();
        }

        // ✅ Abrir la versión resumida del PDF (sin detalle)
        $url = route('invoice.summary', ['hash' => base64_encode($sale->id)]);
        $this->dispatchBrowserEvent('open-print-url', ['url' => $url]);
        $this->dispatchBrowserEvent('close-modal', ['modal' => 'printOptionsModal']);
    }



   public function generateSummaryInvoicePDF($hash)
    {
        [$sale, $company] = InvoiceService::sale(base64_decode($hash), true);

        return view('pdf.invoice.invoice_legal', [
            'sale' => $sale,
            'company' => $company,
            'auto_print' => true,
        ]);
    }

}
