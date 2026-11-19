<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Preventas</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('preview-sales.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Preventa
            </a>
            <a href="{{ route('preview-sales.report') }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Reporte
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select wire:model.live="status" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="confirmed">Confirmada</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <select wire:model.live="customer_id" class="form-select">
                        <option value="">Todos los clientes</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" wire:model.live="date_from" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" wire:model.live="date_to" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" wire:click="resetFilters" class="btn btn-secondary w-100">
                        <i class="fas fa-redo"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de preventas -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#ID</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Pagado</th>
                        <th>Pendiente</th>
                        <th>Fecha Entrega</th>
                        <th>Creada</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($previewSales as $preventa)
                        <tr>
                            <td><strong>#{{ $preventa->id }}</strong></td>
                            <td>{{ optional($preventa->customer)->display_name }}</td>
                            <td>
                                @if($preventa->status == 'pending')
                                    <span class="badge bg-warning">Pendiente</span>
                                @elseif($preventa->status == 'confirmed')
                                    <span class="badge bg-success">Confirmada</span>
                                @else
                                    <span class="badge bg-danger">Cancelada</span>
                                @endif
                            </td>
                            <td>Gs. {{ number_format($preventa->total, 0) }}</td>
                            <td>Gs. {{ number_format($preventa->total_paid ?? 0, 0) }}</td>
                            <td>
                                @php
                                    $pending = $preventa->total - ($preventa->total_paid ?? 0);
                                @endphp
                                <span class="{{ $pending > 0 ? 'text-danger' : 'text-success' }}">
                                    Gs. {{ number_format($pending, 0) }}
                                </span>
                            </td>
                            <td>{{ $preventa->expected_delivery_date->format('d/m/Y') }}</td>
                            <td>{{ $preventa->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('preview-sales.show', $preventa) }}" class="btn btn-outline-primary" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($preventa->isActive())
                                        <a href="{{ route('preview-sales.to-pos', $preventa) }}" class="btn btn-outline-success btn-sm" title="Pasar a Ventas">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                        <button wire:click="cancel({{ $preventa->id }})" wire:confirm="¿Cancelar preventa?" 
                                            class="btn btn-outline-danger btn-sm" title="Cancelar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <p class="text-muted">No hay preventas</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <div class="mt-4">
        {{ $previewSales->links('pagination::bootstrap-4') }}
    </div>
</div>
