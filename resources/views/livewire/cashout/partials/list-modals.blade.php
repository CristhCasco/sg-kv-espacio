{{-- Listas de ventas, compras y gastos del período: se abren desde las tarjetas del resumen --}}
@php
    $listModals = [
        'modal-sales-list' => [
            'title' => 'Ventas del período',
            'total' => $totalSales,
            'columns' => ['Cliente', 'Items', 'Total'],
            'rows' => collect($sales)->map(fn($sale) => [optional($sale->customer)->display_name, Num::qty($sale->items), number_format($sale->total, 0)]),
            'empty' => 'Sin ventas en el período',
        ],
        'modal-purchases-list' => [
            'title' => 'Compras del período',
            'total' => $totalPurchases,
            'columns' => ['Proveedor', 'Items', 'Total'],
            'rows' => collect($purchases)->map(fn($purchase) => [$purchase->supplier->display_name ?? 'Sin proveedor', Num::qty($purchase->items), number_format($purchase->total, 0)]),
            'empty' => 'Sin compras en el período',
        ],
        'modal-expenses-list' => [
            'title' => 'Gastos extras del período',
            'total' => $totalExtraExpenses,
            'columns' => ['Fecha', 'Descripción', 'Monto'],
            'rows' => collect($extraExpenses)->map(fn($expense) => [\Carbon\Carbon::parse($expense->date)->format('d/m/Y'), $expense->description, number_format($expense->amount, 0)]),
            'empty' => 'Sin gastos en el período',
        ],
    ];
@endphp

@foreach($listModals as $id => $modal)
    <div wire:ignore.self id="{{ $id }}" class="modal fade arq-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $modal['title'] }}</h5>
                        <small class="text-muted">{{ count($modal['rows']) }} registro(s) · Total {{ number_format($modal['total'], 0) }} Gs.</small>
                    </div>
                    <button class="close" data-dismiss="modal" type="button" aria-label="Cerrar">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <table class="table arq-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ $modal['columns'][0] }}</th>
                                <th @if($id != 'modal-expenses-list') class="text-right" @endif>{{ $modal['columns'][1] }}</th>
                                <th class="text-right">{{ $modal['columns'][2] }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($modal['rows'] as $row)
                                <tr>
                                    <td>{{ $row[0] }}</td>
                                    <td @if($id != 'modal-expenses-list') class="text-right" @endif>{{ $row[1] }}</td>
                                    <td class="text-right">{{ $row[2] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="arq-empty">{{ $modal['empty'] }}</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($modal['rows']) > 0)
                            <tfoot>
                                <tr>
                                    <td colspan="2">Total</td>
                                    <td class="text-right">{{ number_format($modal['total'], 0) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                @if($id == 'modal-expenses-list')
                    <div class="modal-footer">
                        <button class="btn btn-sm btn-outline-warning" data-dismiss="modal" wire:click="$emit('show-expense-modal')">+ Agregar gasto</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endforeach
