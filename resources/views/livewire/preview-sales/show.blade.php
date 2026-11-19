<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Preventa #{{ $previewSale->id }}</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('preview-sales') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Panel Izquierdo -->
        <div class="col-md-8">
            <!-- Datos Generales -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">Datos Generales</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Estado:</strong></p>
                            @if($previewSale->status == 'pending')
                                <span class="badge bg-warning fs-6">Pendiente</span>
                            @elseif($previewSale->status == 'confirmed')
                                <span class="badge bg-success fs-6">Confirmada</span>
                            @else
                                <span class="badge bg-danger fs-6">Cancelada</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p><strong>Cliente:</strong></p>
                            <p>{{ $previewSale->customer->name }}<br>
                            <small class="text-muted">{{ $previewSale->customer->full_document ? $previewSale->customer->document_label . ' ' . $previewSale->customer->full_document : 'Sin documento' }}</small></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Fecha Creación:</strong> {{ $previewSale->created_at->format('d/m/Y H:i') }}</p>
                            <p><strong>Creada por:</strong> {{ $previewSale->createdByUser->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Fecha Entrega Esperada:</strong> {{ $previewSale->expected_delivery_date->format('d/m/Y') }}</p>
                            @if($previewSale->confirmed_at)
                                <p><strong>Confirmada:</strong> {{ $previewSale->confirmed_at->format('d/m/Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    @if($previewSale->notes)
                        <hr>
                        <p><strong>Notas:</strong></p>
                        <p class="text-muted">{{ $previewSale->notes }}</p>
                    @endif
                </div>
            </div>

            <!-- Productos -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">Productos en Preventa</h5>
                    @if($previewSale->isActive())
                        <button type="button" wire:click="goToAddProducts" class="btn btn-sm btn-light">
                            <i class="fas fa-plus"></i> Agregar Productos
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                                @if($previewSale->isActive())
                                    <th class="text-center" width="120">Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewSale->details as $detail)
                                <tr>
                                    <td>
                                        <strong>{{ $detail->product->name }}</strong>
                                        @if($detail->notes)
                                            <br><small class="text-muted">{{ $detail->notes }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ Num::qty($detail->quantity) }}
                                    </td>
                                    <td class="text-end">{{ number_format($detail->price, 0) }} Gs.</td>
                                    <td class="text-end"><strong>{{ number_format($detail->quantity * $detail->price, 0) }} Gs.</strong></td>
                                    @if($previewSale->isActive())
                                        <td class="text-center">
                                            <button type="button" 
                                                wire:click="removeProduct({{ $detail->id }})"
                                                wire:confirm="¿Remover este producto?"
                                                class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            <tr class="table-light">
                                <td colspan="{{ $previewSale->isActive() ? '4' : '3' }}" class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end"><strong>{{ number_format($previewSale->total, 0) }} Gs.</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Panel Derecho -->
        <div class="col-md-4">
            <!-- Resumen -->
            <div class="card sticky-top mb-4" style="top: 20px;">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Resumen Financiero</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-2 border-bottom">
                        <p class="mb-1"><small class="text-muted">Total</small></p>
                        <h4>{{ number_format($previewSale->total, 0) }} Gs.</h4>
                    </div>
                    <div class="mb-3 pb-2 border-bottom">
                        <p class="mb-1"><small class="text-muted">Pagado</small></p>
                        <h5 class="text-success">{{ number_format($previewSale->total_paid ?? 0, 0) }} Gs.</h5>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1"><small class="text-muted">Pendiente</small></p>
                        <h5 class="{{ $previewSale->pending_payment > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($previewSale->pending_payment ?? 0, 0) }} Gs.
                        </h5>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0 text-white">Acciones</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    @if($previewSale->isActive())
                        <a href="{{ route('preview-sales.to-pos', $previewSale) }}" class="btn btn-success">
                            <i class="fas fa-arrow-right"></i> Pasar a Ventas
                        </a>
                        <button type="button" wire:click="goToAddProducts" class="btn btn-info">
                            <i class="fas fa-edit"></i> Editar Productos
                        </button>
                        <button type="button" wire:click="cancel" wire:confirm="¿Cancelar preventa? El stock será restaurado." 
                            class="btn btn-danger">
                            <i class="fas fa-times"></i> Cancelar Preventa
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
