<div class="row mt-3 mb-3">
    <div class="col-sm-12 col-md-3">
        <div class="form-group">
            <label>Tipo de Pago</label>
            <select wire:model="paymentType" class="form-control">
                <option value="">Todos</option>
                <option value="CONTADO">CONTADO</option>
                <option value="CREDITO">CREDITO</option>
            </select>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="form-group">
            <label>Método de Pago</label>
            <select wire:model="paymentMethod" class="form-control">
                <option value="">Todos</option>
                <option value="EFECTIVO">EFECTIVO</option>
                <option value="TDD">TDD</option>
                <option value="TDC">TDC</option>
                <option value="TRANS">TRANS</option>
                <option value="TIGO">TIGO</option>
                <option value="CHEQUE">CHEQUE</option>
                <option value="OTROS">OTROS</option>
            </select>
        </div>
    </div>

    <div class="col-sm-12 col-md-3 align-self-center">
        <button wire:click.prevent="Consult" type="button" class="btn btn-dark btn-block">
            Filtrar
        </button>
    </div>
</div>

<!-- Métricas de Ventas -->
<div class="row mt-3">
    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting bg-dark p-4 text-center">
            <h6 class="text-white mb-2">Total Vendido</h6>
            <h4 class="text-white">{{ number_format($totalSales, 0) }} Gs.</h4>
        </div>
    </div>

    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #28a745">
            <h6 class="text-white mb-2">Contado</h6>
            <h4 class="text-white">{{ number_format($totalContado, 0) }} Gs.</h4>
        </div>
    </div>

    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #0056b3">
            <h6 class="text-white mb-2">Crédito</h6>
            <h4 class="text-white">{{ number_format($totalCredito, 0) }} Gs.</h4>
        </div>
    </div>

    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #6c757d">
            <h6 class="text-white mb-2">Artículos</h6>
            <h4 class="text-white">{{ Num::qty($itemsSales) }}</h4>
        </div>
    </div>
</div>

<!-- Tabla de Ventas -->
<div class="row mt-4">
    <div class="col-sm-12">
        <h5 class="text-center text-dark mb-3">Historial de Ventas</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped mt-1">
                <thead class="text-white" style="background: #620408;">
                    <tr>
                        <th class="text-center">ID</th>
                        <th class="text-left">Cliente</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Tipo Pago</th>
                        <th class="text-center">Método</th>
                        <th class="text-center">Artículos</th>
                        <th class="text-center">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td class="text-center">{{ $sale->id }}</td>
                            <td class="text-left">{{ optional($sale->customer)->display_name ?? 'Sin cliente' }}</td>
                            <td class="text-center">{{ number_format($sale->total, 0) }} Gs.</td>
                            <td class="text-center">
                                <span class="badge {{ $sale->payment_type === 'CONTADO' ? 'badge-success' : 'badge-info' }}">
                                    {{ $sale->payment_type }}
                                </span>
                            </td>
                            <td class="text-center">{{ $sale->payment_method }}</td>
                            <td class="text-center">{{ Num::qty($sale->items) }}</td>
                            <td class="text-center">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No hay ventas para este período</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>