<div class="row mt-3">
    <!-- Total Vendido -->
    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting bg-dark p-4 text-center">
            <h6 class="text-white mb-2">Total Vendido</h6>
            <h4 class="text-white">{{ number_format($totalSales, 0) }} Gs.</h4>
            <small class="text-muted">{{ Num::qty($itemsSales) }} art�culos</small>
        </div>
    </div>

    <!-- Contado vs Cr�dito -->
    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #023b58">
            <h6 class="text-white mb-2">Por Tipo de Pago</h6>
            <p class="text-white mb-1">
                ?? Contado: <strong>{{ number_format($totalContado, 0) }} Gs.</strong>
            </p>
            <p class="text-white">
                ?? Cr�dito: <strong>{{ number_format($totalCredito, 0) }} Gs.</strong>
            </p>
        </div>
    </div>

    <!-- Gesti�n de Cr�ditos -->
    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #856404">
            <h6 class="text-white mb-2">Cr�ditos</h6>
            <p class="text-white mb-1">
                Cobrado: <strong>{{ number_format($totalCreditPaid, 0) }} Gs.</strong>
            </p>
            <p class="text-white">
                Pendiente: <strong>{{ number_format($totalCreditPending, 0) }} Gs.</strong>
            </p>
        </div>
    </div>

    <!-- Diferencia Ajustada -->
    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        @php
            $diferencia = $totalSales - $totalPurchases - $totalExtraExpenses;
            if ($diferencia > 0) {
                $bgColor = '#28a745';
            } elseif ($diferencia < 0) {
                $bgColor = '#dc3545';
            } else {
                $bgColor = '#6c757d';
            }
        @endphp
        <div class="connect-sorting p-4 text-center" style="background: {{ $bgColor }}">
            <h6 class="text-white mb-2">Diferencia Ajustada</h6>
            <h4 class="text-white">
                @if($diferencia > 0)
                    🟢 +{{ number_format($diferencia, 0) }} Gs.
                @elseif($diferencia < 0)
                    🔴 {{ number_format($diferencia, 0) }} Gs.
                @else
                    ?? Equilibrio
                @endif
            </h4>
        </div>
    </div>
</div>

<!-- Resumen Detallado -->
<div class="row mt-4">
    <!-- Ventas -->
    <div class="col-sm-12 col-md-4">
        <div class="connect-sorting bg-dark p-3">
            <h5 class="text-white text-center">Ventas Totales</h5>
            <h6 class="text-white">Total: {{ number_format($totalSales, 0) }} Gs.</h6>
            <h6 class="text-white">Art�culos: {{ Num::qty($itemsSales) }}</h6>
        </div>
    </div>

    <!-- Compras -->
    <div class="col-sm-12 col-md-4">
        <div class="connect-sorting p-3" style="background: #023b58">
            <h5 class="text-white text-center">Compras Totales</h5>
            <h6 class="text-white">Total: {{ number_format($totalPurchases, 0) }} Gs.</h6>
            <h6 class="text-white">Art�culos: {{ Num::qty($itemsPurchases) }}</h6>
        </div>
    </div>

    <!-- Gastos Extras -->
    <div class="col-sm-12 col-md-4">
        <div class="connect-sorting bg-warning p-3">
            <h5 class="text-white text-center">Gastos Extras</h5>
            <h6 class="text-white">Total: {{ number_format($totalExtraExpenses, 0) }} Gs.</h6>
            <button wire:click="$emit('show-expense-modal')" class="btn btn-light btn-sm btn-block mt-2">
                + Agregar Gasto
            </button>
        </div>
    </div>
</div>
