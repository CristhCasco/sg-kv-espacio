<div class="container-fluid px-4">
    <h2 class="mb-4">Confirmar Preventa #{{ $previewSale->id }}</h2>

    <div class="row">
        <div class="col-md-8">
            <form wire:submit.prevent="confirm">
                <!-- Items Actuales -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-white">Productos en la Preventa</h5>
                    </div>
                    <div class="card-body">
                        @foreach($items as $index => $item)
                            <div class="row mb-3 p-3 border rounded align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label"><strong>{{ $item['product_name'] }}</strong></label>
                                    <input type="hidden" wire:model="items.{{ $index }}.id">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" wire:model="items.{{ $index }}.quantity" 
                                        class="form-control" step="0.1" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Precio</label>
                                    <input type="text" class="form-control" value="Gs. {{ number_format($item['price'], 0) }}" disabled>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Subtotal</label>
                                    <strong>Gs. {{ number_format($item['quantity'] * $item['price'], 0) }}</strong>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" wire:click="removeItem({{ $item['id'] }})" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Agregar Nuevos Productos -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0 text-white">Agregar Productos Adicionales</h5>
                    </div>
                    <div class="card-body">
                        @foreach($newProducts as $index => $item)
                            <div class="row mb-3 p-3 border rounded align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Producto</label>
                                    <select wire:model="newProducts.{{ $index }}.product_id" class="form-select">
                                        <option value="">Seleccionar producto</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" wire:model="newProducts.{{ $index }}.quantity" 
                                        class="form-control" step="0.1" value="0">
                                </div>
                                <div class="col-md-4 d-flex gap-2">
                                    @if($loop->last)
                                        <button type="button" wire:click="addNewProduct" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-plus"></i> Agregar
                                        </button>
                                    @else
                                        <button type="button" wire:click="removeNewProduct({{ $index }})" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Botones -->
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmar Preventa
                    </button>
                    <a href="{{ route('preview-sales.show', $previewSale) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>

        <!-- Resumen -->
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Resumen Final</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2 pb-2 border-bottom">
                        <p class="mb-1"><small class="text-muted">Cantidad Items</small></p>
                        <p>{{ count($items) + count(array_filter($newProducts, fn($p) => !empty($p['product_id']))) }}</p>
                    </div>
                    <div class="mb-2 pb-2 border-bottom">
                        <p class="mb-1"><small class="text-muted">Total Actual</small></p>
                        <h4>Gs. {{ number_format($previewSale->total, 0) }}</h4>
                    </div>
                    <div class="mb-2">
                        <p class="mb-1"><small class="text-muted">Nuevo Total</small></p>
                        <h4 class="text-primary">Gs. {{ number_format($total, 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
