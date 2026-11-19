<div class="row mt-3">
    <!-- Tabla de Compras -->
    <div class="col-sm-12 col-md-4">
        <h5 class="text-center text-dark">Historial de Compras</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="text-white" style="background: #023b58;">
                    <tr>
                        <th class="text-center">PROVEEDOR</th>
                        <th class="text-center">TOTAL</th>
                        <th class="text-center">ITEMS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td class="text-left text-xs">{{ $purchase->supplier->display_name ?? 'Sin proveedor' }}</td>
                            <td class="text-center">{{ number_format($purchase->total, 0) }} Gs.</td>
                            <td class="text-center">{{ Num::qty($purchase->items) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Sin compras</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabla de Gastos Extras -->
    <div class="col-sm-12 col-md-4">
        <h5 class="text-center text-dark">Historial de Gastos Extras</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="text-white" style="background: #856404;">
                    <tr>
                        <th class="text-center">FECHA</th>
                        <th class="text-center">DESCRIPCIÓN</th>
                        <th class="text-center">MONTO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($extraExpenses as $expense)
                        <tr>
                            <td class="text-center text-xs">{{ $expense->date }}</td>
                            <td class="text-left text-xs">{{ $expense->description }}</td>
                            <td class="text-center">{{ number_format($expense->amount, 0) }} Gs.</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Sin gastos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabla de Ventas Completa -->
    <div class="col-sm-12 col-md-4">
        <h5 class="text-center text-dark">Últimas Ventas</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="text-white" style="background: #620408;">
                    <tr>
                        <th class="text-center">CLIENTE</th>
                        <th class="text-center">TOTAL</th>
                        <th class="text-center">ITEMS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td class="text-left text-xs">{{ optional($sale->customer)->display_name ?? 'Sin cliente' }}</td>
                            <td class="text-center">{{ number_format($sale->total, 0) }} Gs.</td>
                            <td class="text-center">{{ Num::qty($sale->items) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Sin ventas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
