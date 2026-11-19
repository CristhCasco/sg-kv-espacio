<div class="row mt-3">
    <!-- Crédito Emitido -->
    <div class="col-sm-12 col-md-4 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #856404">
            <h6 class="text-white mb-2">Crédito Emitido</h6>
            <h4 class="text-white">{{ number_format($totalCreditIssued, 0) }} Gs.</h4>
            <small class="text-white">Total de créditos otorgados</small>
        </div>
    </div>

    <!-- Crédito Cobrado -->
    <div class="col-sm-12 col-md-4 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #28a745">
            <h6 class="text-white mb-2">Crédito Cobrado</h6>
            <h4 class="text-white">{{ number_format($totalCreditPaid, 0) }} Gs.</h4>
            <small class="text-white">Ya pagado</small>
        </div>
    </div>

    <!-- Crédito Pendiente -->
    <div class="col-sm-12 col-md-4 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #dc3545">
            <h6 class="text-white mb-2">Crédito Pendiente</h6>
            <h4 class="text-white">{{ number_format($totalCreditPending, 0) }} Gs.</h4>
            <small class="text-white">Falta cobrar</small>
        </div>
    </div>
</div>