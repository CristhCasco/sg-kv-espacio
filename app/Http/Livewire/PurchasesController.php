<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Models\Product;
use Livewire\Component;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Traits\CartTrait;
use Illuminate\Support\Arr;
use App\Models\Denomination;
use App\Models\PurchaseDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchasesController extends Component
{


    //use CartTrait;

    protected $listeners = [
        'addProduct' => 'addProduct',
        'removeItem',
        'clear', // botón "CANCELAR F4" (Confirm emite 'clear')
        'clearCart' => 'clear', // atajo F4 (emite 'clearCart', como en Ventas)
        'print-last' => 'printLastPurchase', // atajo F6
        'addProductFromPurchase',
        'setSupplier'
    ];

    //laravel collections 
    public $carrito;
    public $totalCarrito = 0;
    public $totalItems = 0; // popiedad para guardar la cantidad de productos agregados al carrito
    public $total, $itemsQuantity, $efectivo, $change, $price, $suppliers = [], $supplierId, $supplierName,
        $status, $payment_type, $payment_method, $discount, $discount_total;
    public $margen_ganancia = 30; // Valor por defecto del 30%
    public $due_date;



    function mount()
    {

        $this->efectivo = 0;
        $this->change = 0;
        //$this->total = totalCart();
        //$this->itemsQuantity = totalItems();
        $this->suppliers = Supplier::orderBy('name', 'asc')->get();
        $this->status = 'PAGADO';
        $this->payment_type = 'CONTADO';
        $this->payment_method = 'EFECTIVO';
        $this->discount = 0;
        $this->discount_total = 0;


        // Asignar PROVEEDOR ocasional por defecto
        $occasionalsupplierId = 1; // Reemplaza con el ID real del cliente ocasional
        $occasionalSupplier = Supplier::find($occasionalsupplierId);
        if ($occasionalSupplier) {
            $this->supplierId = $occasionalSupplier->id;
            $this->supplierName = $occasionalSupplier->display_name;
        }

        // Proveedor elegido antes de recargar la página (se guarda en sesión como el carrito)
        if (session()->has('purchaseSupplierId') && $saved = Supplier::find(session('purchaseSupplierId'))) {
            $this->supplierId = $saved->id;
            $this->supplierName = $saved->display_name;
        }

        if (session()->has("carrito")) {
            $this->carrito = session("carrito");
        } else {
            $this->carrito = new Collection; //nueva coleccion de laravel
        }
        //$this->carrito = $this->carrito->sortBy(['name', ['name', 'asc']]);
        $this->carrito = collect(session('carrito', []));
    }


    // public function getContent(): Collection
    // {
    //     return $this->carrito->sortBy(['name', ['name', 'asc']]);

    // }
    function addProductFromPurchase($code)
    {
        // dd($code);
        $this->addProduct(0, 1, $code);
    }

    public function addProduct($productId, $qty = 1, $barcode = null) //1
    {
        if ($barcode == null)
            $product = Product::find($productId);
        else
            $product = Product::where('barcode', trim((string) $barcode))->first();

        if (!$product) {
            $this->emit('scan-notfound', 'El producto no está registrado');
            return;
        }

        $coll = collect(
            [
                'id' => $product->id,
                'name' => $product->name,
                'qty' => $qty,
                // Último costo conocido del producto (antes era 1 y, si no se cambiaba, se guardaba como costo real)
                'cost' => (float) $product->cost > 0 ? (float) $product->cost : 0,
                'total' => ((float) $product->cost > 0 ? (float) $product->cost : 0) * $qty,
                'image' => $product->image
            ]
        );

        //si no esta en el carrito lo inserta
        if (!$this->InCart($product->id)) {

            $pre = Arr::add($coll, null, null);
            $this->carrito->push($pre);
            $this->save();
            $this->emit('purchase-ok', 'Producto agregado');

            //
        } else {
            $this->Increment($product->id, $qty);
        }
    }


    //Esta función, InCart, está diseñada para verificar si un producto específico
    // ya está en el carrito de compras.
    public function InCart($id)
    {
        $mycart = $this->carrito;
        $cont = $mycart->where('id', $id)->count();
        return $cont > 0 ? true : false;
    }

    public function Increment($id, $cant = 1, $replace = false)
    //$cant =1;
    {
        $mycart = $this->carrito;
        $oldItem = $mycart->where('id', $id)->first();
        $newItem = $oldItem;

        if ($replace)
            $newItem['qty'] = $cant;
        else
            $newItem['qty'] += $cant;


        $newItem['total'] =   $newItem['qty'] * $newItem['cost'];

        //delete from cart       
        $this->carrito = $this->carrito->reject(function ($product) use ($id) {
            return $product['id'] === $id;
        });
        $this->save();

        //add item to cart           
        $this->carrito->push(Arr::add(
            $newItem,
            null,
            null
        ));
        $this->save();

        $this->emit('purchase-ok', 'Producto actualizado');
    }


    public function UpdateQty($id, $cant = 1)
    {
        $mycart = $this->carrito;
        $oldItem = $mycart->where('id', $id)->first();
        $newItem = $oldItem;


        $newItem['qty'] = $cant;

        $newItem['total'] =   $newItem['qty'] * $newItem['cost'];

        //delete from cart       
        $this->carrito = $this->carrito->reject(function ($product) use ($id) {
            return $product['id'] === $id;
        });
        $this->save();

        if ($cant > 0) {
            //add item to cart           
            $this->carrito->push(Arr::add(
                $newItem,
                null,
                null
            ));
            $this->save();

            $this->emit('purchase-ok', 'Producto actualizado');
        } else {
            // -1, se elimina producto del carrito
        }
    }


    public function setCost($id, $cost = 1)
    {

        if (empty($cost) || strlen($cost) < 1 || is_numeric(!$cost)) {
            $this->emit('purchase-error', 'El Costo es Inválido');
            return;
        }


        $mycart = $this->carrito;
        $oldItem = $mycart->where('id', $id)->first();
        $newItem = $oldItem;

        $newItem['cost'] = $cost;

        $newItem['total'] =   $newItem['qty'] * $newItem['cost'];

        //delete from cart       
        $this->carrito = $this->carrito->reject(function ($product) use ($id) {
            return $product['id'] === $id;
        });
        $this->save();

        //add item to cart           
        $this->carrito->push(Arr::add(
            $newItem,
            null,
            null
        ));
        $this->save();

        $this->emit('purchase-ok', 'Precio de Compra actualizado');
    }



    //eliminar producto
    public function removeItem($id)
    {
        //dd($id);//4
        $this->carrito = $this->carrito->reject(function ($product) use ($id) {
            return $product['id'] === intval($id);
        });
        $this->save();
    }



    // F6: vuelve a abrir el comprobante de la última compra del usuario conectado
    public function printLastPurchase()
    {
        $purchase = Purchase::where('user_id', auth()->id())->latest('id')->first();

        if (!$purchase) {
            $this->dispatchBrowserEvent('purchase-error', ['msg' => 'No tienes compras para reimprimir.']);
            return;
        }

        $this->dispatchBrowserEvent('comprobante-compra-generado', [
            'url' => route('purchase.ticket', base64_encode($purchase->id))
        ]);
    }

    public function clear()
    {
        $this->carrito = new Collection;
        $this->save();
    }


    public function save()
    {
        session()->put("carrito", $this->carrito);
        session()->save();
    }



    // public function totalCart()
    // {
    //     $amount = $this->carrito->sum(function ($product) {
    //         return round($product['total']);
    //     });
    //     return $amount;
    // }

        public function totalCart()
    {
        return round($this->subtotalCart(), 2);
    }



    public function totalItems()
    {
        $items = $this->carrito->sum(function ($product) {
            return $product['qty'];
        });
        return $items;
    }


    public function render()
    {
        //$this->removeItem(4);   
        // $this->clear();
        // $producto = Product::find(1);
        //$this->addProduct($producto, 2);  

        $this->totalCarrito = $this->totalCart();  //obtienes el total de los productos agregados al carrito
        $this->totalItems = $this->totalItems();

        $this->carrito = $this->carrito->sortBy(['name', 'desc']);

        return view('livewire.purchases.component2', [
            'denominations' => Denomination::get(),
            'carrito' => $this->carrito
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    // public function subtotalCart()
    // {
    //     $subtNeto = $this->carrito->sum(function ($product) {

    //         return $product['cost'] * $product['qty'];
    //     });

    //     return $subtNeto;
    // }

        public function subtotalCart()
    {
        return round($this->carrito->sum(function ($product) {
            return round($product['cost'], 2) * round($product['qty'], 3);
        }), 2);
    }


    public function savePurchase()
    {
        $supplier = Supplier::find($this->supplierId);

        if (!$supplier) {
            $this->dispatchBrowserEvent('purchase-error', ['msg' => 'Proveedor no encontrado.']);
            return;
        }

        // ⚠️ Bloquear si proveedor es ocasional o "desconocido" y la compra es a crédito
        // (display_name: las empresas no tienen name; se acepta también la grafía "ocacional")
        $nombre = strtolower(trim($supplier->display_name));
        if ($this->payment_type === 'CREDITO' && in_array($nombre, ['ocasional', 'ocacional', 'desconocido'])) {
            $this->dispatchBrowserEvent('purchase-error', ['msg' => 'No se permite hacer compras a crédito con proveedores ocasionales o desconocidos.']);
            return;
        }

        if ($this->payment_type == 'CONTADO' && $this->payment_method == 'EFECTIVO') {
            if (empty($this->efectivo) || !is_numeric($this->efectivo) || $this->efectivo <= 0) {
                $this->dispatchBrowserEvent('purchase-error', ['msg' => 'EL MONTO DEL EFECTIVO ES INVÁLIDO']);
                return;
            }
        }

        if ($this->supplierId == null) {
            $this->dispatchBrowserEvent('purchase-error', ['msg' => 'SELECCIONA EL PROVEEDOR']);
            return;
        }

        // Al guardar, el costo de la compra reemplaza el costo del producto (y con margen, su precio):
        // no se permite guardar costos, cantidades o márgenes inválidos.
        if (collect($this->carrito)->isEmpty()) {
            $this->dispatchBrowserEvent('purchase-error', ['msg' => 'AGREGA PRODUCTOS A LA COMPRA']);
            return;
        }

        foreach ($this->carrito as $item) {
            $name = $item['name'] ?? 'Producto';

            if (!isset($item['qty']) || !is_numeric($item['qty']) || $item['qty'] <= 0) {
                $this->dispatchBrowserEvent('purchase-error', ['msg' => "LA CANTIDAD DE \"{$name}\" DEBE SER MAYOR A 0"]);
                return;
            }
            if (!isset($item['cost']) || !is_numeric($item['cost']) || $item['cost'] <= 0) {
                $this->dispatchBrowserEvent('purchase-error', ['msg' => "INGRESA EL COSTO DE \"{$name}\" (DEBE SER MAYOR A 0)"]);
                return;
            }
            if (isset($item['margen']) && $item['margen'] !== '' && $item['margen'] !== null
                && (!is_numeric($item['margen']) || $item['margen'] < 0)) {
                $this->dispatchBrowserEvent('purchase-error', ['msg' => "EL MARGEN DE \"{$name}\" NO ES VÁLIDO"]);
                return;
            }
        }

        DB::beginTransaction();

        try {
            $status = $this->payment_type == 'CREDITO' ? 'PENDIENTE' : 'PAGADO';

            $purchase = Purchase::create([
                'items'          => $this->totalItems(),
                'sub_total'      => round($this->subtotalCart(), 2),
                'total'          => $this->totalCart(),
                'cash'           => $this->efectivo,
                'change'         => 0,
                'status'         => $status,
                'payment_type'   => $this->payment_type,
                'payment_method' => $this->payment_method,
                'discount'       => 0,
                'discount_total' => 0,
                'supplier_id'    => $this->supplierId,
                'user_id'        => auth()->id()
            ]);

            foreach ($this->carrito as $item) {
                PurchaseDetail::create([
                    'price'       => $item['cost'],
                    'quantity'    => $item['qty'],
                    'product_id'  => $item['id'],
                    'purchase_id' => $purchase->id,
                    'total'       => $item['total']
                ]);

                $product = Product::find($item['id']);
                $product->stock += $item['qty'];
                $product->cost = $item['cost'];

                if (isset($item['margen']) && is_numeric($item['margen']) && $item['margen'] >= 0) {
                    $ganancia = $item['cost'] * ($item['margen'] / 100);
                    $product->price = round($item['cost'] + $ganancia, 2);
                }

                $product->save();
            }

            // Crear el crédito
            if ($this->payment_type === 'CREDITO') {
                $credit = $purchase->credit()->create([
                    'supplier_id'       => $this->supplierId,
                    'total_credit'      => $this->totalCart(),
                    'amount_paid'       => $this->efectivo > 0 ? $this->efectivo : 0,
                    'remaining_balance' => $this->totalCart() - $this->efectivo,
                    'status'            => ($this->efectivo >= $this->totalCart()) ? 'PAGADO' : 'PENDIENTE',
                    'due_date'          => $this->due_date ?? now()->addDays(30),
                ]);

                if ($this->efectivo > 0) {
                    $credit->payments()->create([
                        'amount_paid'  => $this->efectivo,
                        'payment_date' => now(),
                        'user_id'      => auth()->id()
                    ]);

                    // Si pagó todo en el momento, también se actualiza la compra
                    if ($credit->status === 'PAGADO') {
                        $purchase->status = 'PAGADO';
                        $purchase->save();
                    }
                }
            }

            
            $this->dispatchBrowserEvent('comprobante-compra-generado', [
                'url' => route('purchase.ticket', base64_encode($purchase->id))
            ]);


            DB::commit();

            $this->clear();
            $this->efectivo = 0;
            $this->change = 0;

            $this->dispatchBrowserEvent('purchase-ok', ['msg' => 'Compra Registrada']);
            $this->resetSupplier();
            $this->resetPaymentInfo();
            $this->mostrarResumen = false;

        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatchBrowserEvent('purchase-error', ['msg' => $th->getMessage()]);
        }
    }

    

    public function ACash($value)
    {
        $this->efectivo =  $this->totalCarrito;
        $this->change = ($this->efectivo - $this->totalCarrito);
    }

    public function setSupplier($supplierId, $supplierName)
    {
        $this->supplierName = $supplierName;
        $this->supplierId = $supplierId;
        session(['purchaseSupplierId' => $supplierId]);
    }

    public function resetSupplier()
    {
        session()->forget('purchaseSupplierId');
        $this->supplierId = '';
        $this->supplierName = '';
    }

    public function updateCost($id, $cost)
    {
        // Antes un costo con letras o negativo se convertía en 0 sin avisar
        if ($cost !== null && $cost !== '' && (!is_numeric($cost) || $cost < 0)) {
            $this->dispatchBrowserEvent('purchase-error', ['msg' => 'EL COSTO DEBE SER UN NÚMERO MAYOR O IGUAL A 0']);
            return;
        }

        foreach ($this->carrito as $index => $item) {
            if ($item['id'] == $id) {
                $this->carrito[$index]['cost'] = is_numeric($cost) ? floatval($cost) : 0;
                $this->carrito[$index]['total'] = round($this->carrito[$index]['qty'] * $this->carrito[$index]['cost'], 2);

                // Guarda apenas se modifica
                session()->put('carrito', $this->carrito);
                session()->save();

                break;
            }
        }

        $this->totalCarrito = $this->totalCart();
        $this->totalItems = $this->totalItems();

        $this->emit('purchase-ok', 'Costo actualizado automáticamente');
    }

    public function updatedCarrito($value, $key)
    {
        [$index, $field] = explode('.', $key);

        if (!isset($this->carrito[$index])) return;

        $item = $this->carrito[$index];

        // Antes un costo negativo se aceptaba y una cantidad con letras se cambiaba a 1 sin avisar:
        // ahora se rechaza el valor y se restaura el anterior (guardado en la sesión).
        $errors = [
            'cost'   => (!is_numeric($value) || $value < 0) ? 'EL COSTO DEBE SER UN NÚMERO MAYOR O IGUAL A 0' : null,
            'qty'    => (!is_numeric($value) || $value <= 0) ? 'LA CANTIDAD DEBE SER MAYOR A 0' : null,
            'margen' => ($value !== '' && $value !== null && (!is_numeric($value) || $value < 0)) ? 'EL MARGEN DEBE SER UN NÚMERO MAYOR O IGUAL A 0' : null,
        ];

        if (!empty($errors[$field])) {
            $previous = collect(session('carrito', []))->get($index);
            if ($previous) {
                $this->carrito[$index] = $previous;
            }
            $this->dispatchBrowserEvent('purchase-error', ['msg' => $errors[$field]]);
            return;
        }

        $item['qty'] = isset($item['qty']) && is_numeric($item['qty']) ? floatval($item['qty']) : 1;
        $item['cost'] = isset($item['cost']) && is_numeric($item['cost']) ? floatval($item['cost']) : 0;
        $item['total'] = round($item['qty'] * $item['cost'], 2);

        $this->carrito[$index] = $item;

        // Guardar en la sesión SIEMPRE
        session()->put('carrito', $this->carrito);
        session()->save();

        $this->totalCarrito = $this->totalCart();
        $this->totalItems = $this->totalItems();

        $this->emit('purchase-ok', 'Producto actualizado automáticamente');
    }


    public $mostrarResumen = false;

    public function confirmarResumen()
    {
        $this->mostrarResumen = true;
    }

    public function resetPaymentInfo()
{
    $this->payment_type = 'CONTADO';
    $this->payment_method = 'EFECTIVO';
    $this->due_date = now()->addDays(30)->format('Y-m-d');
    $this->efectivo = 0;
    $this->change = 0;
}





}
