<div class="container-fluid px-4">
    <h2 class="mb-4">Reporte de Preventas</h2>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <select wire:model="status" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="confirmed">Confirmada</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" wire:model="date_from" class="form-control" placeholder="Desde">
                </div>
                <div class="col-md-3">
                    <input type="date" wire:model="date_to" class="form-control" placeholder="Hasta">
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" onclick="window.print()" class="btn btn-primary w-100">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Pendientes</h6>
                    <h3 class="text-warning">{{ $stats['total_pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Confirmadas</h6>
                    <h3 class="text-success">{{ $stats['total_confirmed'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Canceladas</h6>
                    <h3 class="text-danger">{{ $stats['total_cancelled'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Monto</h6>
                    <h3 class="text-primary">Gs. {{ number_format($stats['total_amount'], 0) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Preventas -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Detalle de Preventas</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#ID</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Cantidad Items</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Pendiente</th>
                        <th>Creada</th>
                        <th>Entrega</th>
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
                            <td>{{ $preventa->details->count() }}</td>
                            <td class="text-end">Gs. {{ number_format($preventa->total, 0) }}</td>
                            <td class="text-end">Gs. {{ number_format($preventa->total_paid ?? 0, 0) }}</td>
                            <td class="text-end">
                                @php
                                    $pending = $preventa->total - ($preventa->total_paid ?? 0);
                                @endphp
                                <span class="{{ $pending > 0 ? 'text-danger' : 'text-success' }}">
                                    Gs. {{ number_format($pending, 0) }}
                                </span>
                            </td>
                            <td>{{ $preventa->created_at->format('d/m/Y') }}</td>
                            <td>{{ $preventa->expected_delivery_date->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No hay preventas para mostrar
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end">TOTALES:</td>
                        <td class="text-end">Gs. {{ number_format($stats['total_amount'], 0) }}</td>
                        <td class="text-end">Gs. {{ number_format($stats['total_paid'], 0) }}</td>
                        <td class="text-end">Gs. {{ number_format($stats['total_amount'] - $stats['total_paid'], 0) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('preview-sales') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
    </div>
</div>

<style>
    @media print {
        .btn, .card-body > div:first-child, .card-header { display: none; }
        body { font-size: 10px; }
    }
</style>
