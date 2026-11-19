<div>
    <div class="row layout-top-spacing">
        <!-- CARRITO Y DETALLES -->
        <div class="col-sm-12 col-md-8">
            <!-- Encabezado -->
            <div class="connect-sorting mb-2">
                <div class="container text-center">
                    <div class="row">
                        <div class="col-12">
                            <div class="card simple-title-task ui-sorteable-handle">
                                <p class="h3 flex-grow-1 text-center">Editar Preventa #{{ $previewSale->id }} - Cliente: {{ optional($previewSale->customer)->display_name }}</p>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12 mt-3">
                            <button class="btn btn-dark btn-block" data-toggle="modal" data-target="#modalSearchProduct">
                                <i class="fas fa-search"></i>
                                Buscar Productos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carrito -->
            <div class="connect-sorting">
                <div class="connect-sorting-content">
                    <div class="card simple-title-task ui-sortable-handle">
                        <div class="card-body">
                            @if($totalItems > 0)
                                <div class="table-responsive tblscroll">
                                    <table class="table bordered table-striped mt-1">
                                        <thead class="text-white" style="background: #620408">
                                            <tr>
                                                <th width="13%" class="table-th text-center text-white">CANTIDAD</th>
                                                <th class="table-th text-left text-white">DESCRIPCIÓN</th>
                                                <th class="table-th text-center text-white">PRECIO</th>
                                                <th class="table-th text-center text-white">SUB TOTAL</th>
                                                <th class="table-th text-center text-white">ACCIONES</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cart as $index => $item)
                                                <tr>
                                                    <td>
                                                        <input type="number" 
                                                            wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                                            style="font-size: 1rem!important"
                                                            class="form-control text-center"
                                                            value="{{ Num::input($item['quantity']) }}"
                                                            step="{{ ($item['is_weighted'] ?? false) ? '0.1' : '1' }}"
                                                            min="{{ ($item['is_weighted'] ?? false) ? '0.1' : '1' }}">
                                                    </td>

                                                    <td>
                                                        <a>{{ $item['name'] }}</a>
                                                    </td>

                                                    <td class="text-center align-middle">
                                                        @php
                                                            $precioBase = floatval($item['price']);
                                                            $descuento = isset($item['discount']) ? floatval($item['discount']) : 0;
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
                                                                    wire:click="toggleDiscountInput({{ $index }})"
                                                                    style="font-size: 0.9rem; line-height: 1;">
                                                                @if(!($showDiscountInput[$index] ?? false))
                                                                    ➕
                                                                @else
                                                                    ✖
                                                                @endif
                                                            </button>
                                                        </div>
                                                    </td>

                                                    <td class="text-right align-middle">
                                                        @php
                                                            $lineTotal = ($precioFinal) * $item['quantity'];
                                                        @endphp
                                                        <strong>{{ number_format($lineTotal, 0) }} Gs.</strong>
                                                    </td>

                                                    <td class="text-center align-middle">
                                                        <button wire:click="removeItem({{ $index }})" class="btn btn-danger btn-sm mbmobile">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                        <button wire:click="decreaseQuantity({{ $index }})" class="btn btn-dark btn-sm mbmobile">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <button wire:click="increaseQuantity({{ $index }})" class="btn btn-dark btn-sm mbmobile">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </td>
                                                </tr>

                                                @if($showDiscountInput[$index] ?? false)
                                                    <tr class="bg-light">
                                                        <td colspan="5">
                                                            <div class="row align-items-center">
                                                                <div class="col-md-6">
                                                                    <label class="form-label mb-0">Descuento (Gs.)</label>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <input type="number" 
                                                                        wire:change="updateDiscount({{ $index }}, $event.target.value)"
                                                                        class="form-control form-control-sm"
                                                                        value="{{ $item['discount'] ?? 0 }}"
                                                                        step="100"
                                                                        min="0">
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <h5 class="text-muted">No hay productos en el carrito</h5>
                                    <p class="text-muted">Busca y agrega productos para editar la preventa</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESUMEN Y DATOS -->
        <div class="col-sm-12 col-md-4">
            <!-- Resumen -->
            <div class="card mb-3 sticky-top" style="top: 20px;">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0 text-white">Resumen</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2 d-flex justify-content-between">
                        <span>Cantidad de Ítems:</span>
                        <strong>{{ Num::qty($totalItems) }}</strong>
                    </div>
                    <hr>
                    <div class="mb-3 d-flex justify-content-between">
                        <span>Total:</span>
                        <strong style="font-size: 1.3rem;">{{ number_format($totalCart, 0) }} Gs.</strong>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">Acciones</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <button type="button" wire:click="save" class="btn btn-success btn-block mb-2" {{ $totalItems === 0 ? 'disabled' : '' }}>
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('preview-sales.show', $previewSale) }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALES -->
    <livewire:modal-search :componentToEmit="4" />

    <script src="{{ asset('js/keypress.js')}}"></script>
    <script src="{{ asset('js/onscan.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('.tblscroll').niceScroll({
                cursoscolor: "#515365",
                cursorwidth: "30px",
                background: "rgba(20,20,20,0.3)",
                cursorborder: "0px",
                cursorborderradius: 3
            })

            // Inicializar scanner de códigos de barras
            onScan.attachTo(document, {
                suffixKeyCodes: [13],
                minLength: 6,
                onScan: function(barcode) {
                    console.log('Código de barras detectado:', barcode);
                    // Buscar el producto por código de barras y agregarlo
                    fetch('{{ url('api/products/barcode') }}/' + encodeURIComponent(barcode), {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(async response => {
                            const data = await response.json().catch(() => ({}));
                            if (response.status === 401) {
                                noty('Tu sesión expiró. Vuelve a iniciar sesión.', 2);
                            } else if (response.ok && data.id) {
                                window.livewire.emit('addProduct', data.id);
                            } else {
                                noty(data.error || ('El producto ' + barcode + ' no está registrado'), 2);
                            }
                        })
                        .catch(error => { console.error('Error:', error); noty('No se pudo buscar el producto. Revisa la conexión.', 2); });
                },
                onScanError: function(e) {
                    console.log('Error en scanner:', e);
                }
            });
        })
    </script>
</div>
