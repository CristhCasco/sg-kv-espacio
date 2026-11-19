<div class="row mt-3">
    <!-- Margen Bruto -->
    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting p-4 text-center bg-dark">
            <h6 class="text-white mb-2">Margen Bruto</h6>
            <h4 class="text-white">{{ Num::pct($grossMarginPercent) }}%</h4>
            <small class="text-muted">{{ number_format($grossMargin, 0) }} Gs.</small>
        </div>
    </div>

    <!-- Margen Neto -->
    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #0056b3">
            <h6 class="text-white mb-2">Margen Neto</h6>
            <h4 class="text-white">{{ Num::pct($netMarginPercent) }}%</h4>
            <small class="text-muted">{{ number_format($netMargin, 0) }} Gs.</small>
        </div>
    </div>

    <!-- ROI -->
    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #28a745">
            <h6 class="text-white mb-2">ROI (Retorno)</h6>
            <h4 class="text-white">{{ Num::pct($roi) }}%</h4>
            <small class="text-muted">Sobre inversión</small>
        </div>
    </div>

    <!-- Costo Total -->
    <div class="col-sm-12 col-md-6 col-lg-3 mb-3">
        <div class="connect-sorting p-4 text-center" style="background: #856404">
            <h6 class="text-white mb-2">Costo Total</h6>
            <h4 class="text-white">{{ number_format($totalCost, 0) }} Gs.</h4>
            <small class="text-muted">De productos vendidos</small>
        </div>
    </div>
</div>