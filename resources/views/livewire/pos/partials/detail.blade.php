<div>
    <style>
        .pos-cart-scroll {
            max-height: 58vh;
            overflow-y: auto;
            overflow-x: hidden;
        }
    </style>

    <div class="connect-sorting mb-2">
        <div class="container text-center">
            <div class="row">
                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="card simple-title-task ui-sorteable-handle">
                        @if($customerName == null)
                            <p class="h3 flex-grow-1 text-center">Seleccione el Cliente</p>
                        @else
                            <p class="h3 flex-grow-1 text-center">Cliente: {{$customerId}} | {{ $customerName }}</p>
                        @endif
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6">
                    <button class="btn btn-dark btn-block mb-3" data-toggle="modal" data-target="#modalSearchCustomer">
                        <i class="fas fa-search"></i>
                        Buscar Clientes
                    </button>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6">
                    <button class="btn btn-dark btn-block" data-toggle="modal" data-target="#modalSearchProduct">
                        <i class="fas fa-search"></i>
                        Buscar Productos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="connect-sorting">
        <div class="connect-sorting-content">
            <div class="card simple-title-task ui-sortable-handle">
                <div class="card-body">
                    @if($total > 0)
                        <div class="table-responsive tblscroll pos-cart-scroll">
                            <table class="table bordered table-striped mt-1">
                                <thead class="text-white" style="background: #620408">
                                    <tr>
                                        <!-- <th class="table-th text-left text-white">CÓDIGO</th> -->
                                        <th width="13%" class="table-th text-center text-white">CANTIDAD</th>
                                        <th class="table-th text-left text-white">DESCRIPCIÓN</th>
                                        <th class="table-th text-center text-white">PRECIO</th>
                                        <th class="table-th text-center text-white">SUB TOTAL</th>
                                        <th class="table-th text-center text-white">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($posCart as $item)
                                        <tr>
                                            <!-- <td class="text-left">
                                                <a>{{$item['barcode']}}</a>
                                            </td> -->

                                            <td>
                                                <input type="number" id="r{{$item['id']}}" wire:key="qty-{{ $item['id'] }}-{{ $cartInputsVersion }}"
                                                    wire:change="updateQuantity({{$item['id']}}, $('#r' + {{$item['id']}}).val() )"
                                                    style="font-size: 1rem!important; min-width: 6rem; padding-left: .25rem; padding-right: .25rem"
                                                    class="form-control text-center"
                                                    @if(isset($item['w']) && intval($item['w']) ==1)
                                                    step="0.001" min="0.001"
                                                    value="{{ Num::input($item['quantity']) }}"
                                                    @else
                                                    step="1" min="1"
                                                    value="{{ number_format($item['quantity'], 0, '.', '') }}"
                                                    @endif
                                                >
                                            </td>

                                            <td>
                                               <a>
                                                   {{$item['name']}}
                                               </a>
                                           </td>
                                           
                                           <td class="text-center align-middle">
                                                @php
                                                    $precioBase = floatval($item['price']);
                                                    $descuento = isset($item['manual_discount']) ? floatval($item['manual_discount']) : 0;
                                                    $precioFinal = max($precioBase - $descuento, 0);
                                                @endphp

                                                <div>
                                                    <strong style="font-size: 0.9rem;">{{ number_format($precioFinal, 0) }} Gs.</strong>
                                                    @if($descuento > 0)
                                                        <br><small class="text-success" style="font-size: 0.75rem;">-{{ number_format($descuento, 0) }} Gs</small>
                                                    @endif
                                                </div>

                                                <div class="mt-1">
                                                    <button class="btn btn-sm btn-light border px-2 py-0"
                                                            wire:click="toggleDiscountInput({{ $item['id'] }})"
                                                            style="font-size: 0.9rem; line-height: 1;">
                                                        @if(!($showDiscountInput[$item['id']] ?? false))
                                                            ➕
                                                        @else
                                                            ✖
                                                        @endif
                                                    </button>
                                                </div>

                                                @if($showDiscountInput[$item['id']] ?? false)
                                                    <div class="mt-1">
                                                        <input type="number"
                                                        wire:key="discount-{{ $item['id'] }}-{{ $cartInputsVersion }}"
                                                        wire:change="updateManualDiscount({{ $item['id'] }}, $event.target.value)"
                                                        class="form-control form-control-sm text-center"
                                                        placeholder="Descuento"
                                                        value="{{ $item['manual_discount'] ?? '' }}">
                                                    </div>
                                                @endif
                                            </td>





                                            <td class="text-center">
                                                {{ number_format($precioFinal * $item['quantity'], 0) }} Gs.
                                            </td>

                                            <td class="text-center">
                                            <button wire:click="removeItem({{ $item['id'] }})" class="btn btn-dark btn-sm mbmobile">
                                                    <i class="fas fa-trash-alt"></i>
                                            </button>
                                                <!-- <button onclick="Confirm('{{$item['id']}}', 'removeItem', 'CONFIRMAS ELIMINAR EL REGISTRO?' )"
                                                    class="btn btn-dark btn-sm mbmobile">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button> -->
                                                <button wire:click.prevent="decreaseQty({{$item['id']}})" class="btn btn-dark btn-sm mbmobile">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <button wire:click.prevent="increaseQty({{$item['id']}})" class="btn btn-dark btn-sm mbmobile">
                                                    <i class="fas fa-plus"></i>
                                                </button>

                                                
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <h5 class="text-center text-muted">Agrega Productos a la venta</h5>
                    @endif
                </div>
                <div wire:loading.inline wire:target="saveSale">
                    <h4 class="text-danger text-center">Guardando Venta...</h4>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="printOptionsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title text-white">Seleccionar tipo de impresión</h5>
            </div>
            <div class="modal-body text-center">
                <button class="btn btn-dark w-100 mb-2"
                        wire:click="printTicket('{{ base64_encode($lastSaleId) }}')">
                    🧾 Imprimir Ticket
                </button>

                @if($customerName != null && $customerName != 'Ocasional')
                    <button class="btn btn-success w-100 mb-2"
                            wire:click="printFacturaLegal('{{ base64_encode($lastSaleId) }}')">
                        🧰 Imprimir Factura Legal
                    </button>

                    <button class="btn btn-primary w-100"
                            wire:click="printSummaryInvoice('{{ base64_encode($lastSaleId) }}')">
                        📄 Factura Sin Detalle
                    </button>
                @else
                    <div class="alert alert-warning mt-2">
                        <small>⚠️ Solo clientes registrados pueden emitir facturas legales</small>
                    </div>
                @endif


            </div>

            </div>
        </div>
    </div>


</div>

<script>

    function Confirm(id, action, message) {
        if (confirm(message)) {
            window.livewire.emit(action, id);
        }
    }

    function showImageModal(imageUrl) {
        document.getElementById('productImage').src = imageUrl;
        $('#imageModal').modal('show');
    }

    // Impresión: según Configuración → Impresión (layouts/theme/print.blade.php)
    function silentPrintPdf(url) {
        sgPrint(url);
    }

    window.addEventListener('open-print-url', (e) => {
        if (e.detail?.url) {
            silentPrintPdf(e.detail.url);
        }
    });

</script>