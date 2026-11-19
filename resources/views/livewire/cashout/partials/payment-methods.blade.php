<div class="arq-card arq-card-table">
    <div class="arq-card-head">Por método de pago</div>
    <div class="table-responsive">
        <table class="table arq-table mb-0">
            <thead>
                <tr>
                    <th>Método</th>
                    <th class="text-right">Contado</th>
                    <th class="text-right">Crédito pagado</th>
                    <th class="text-right">{{ $totalLabel }}</th>
                    <th class="text-right">Crédito pendiente</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $method => $data)
                    <tr>
                        <td><strong>{{ $method }}</strong></td>
                        <td class="text-right">{{ number_format($data['contado'], 0) }}</td>
                        <td class="text-right">{{ number_format($data['credito_pagado'], 0) }}</td>
                        <td class="text-right"><strong>{{ number_format($data['total'], 0) }}</strong></td>
                        <td class="text-right arq-neg">{{ number_format($data['credito_pendiente'], 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="arq-empty">Sin datos</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="text-right">{{ number_format($cash, 0) }}</td>
                    <td class="text-right">{{ number_format($creditPaid, 0) }}</td>
                    <td class="text-right">{{ number_format($cash + $creditPaid, 0) }}</td>
                    <td class="text-right arq-neg">{{ number_format($creditPending, 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
