@php $tw = \App\Models\Company::ticketWidth(); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: monospace;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            width: {{ round($tw * 72 / 25.4, 2) }}px; /* ancho de Configuración, en puntos */
            padding: 0;
            margin: 0;
        }
        .center { text-align: center; }
        .separator {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        /* Company name highlight */
        .company-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    @include('livewire.tickets.partials.logo')
    <div class="center">
        <div class="company-name">{{ strtoupper($company->name ?? '') }}</div>
        {{ $company->address ?? '' }}<br>
        @if(!empty($company->ruc))
            RUC: {{ $company->ruc }}-{{ $company->dv}}<br>
        @endif
        @if(!empty($company->phone))
            Tel: {{ $company->phone }}<br>
        @endif

        <strong>COMPROBANTE DE PAGO</strong><br>
        {{ $fecha }}
    </div>

    <div class="separator"></div>
    
    Cliente: {{ $cliente }}<br>
    Estado: {{ strtoupper($estado_credito) }}<br>
    Venta: #{{ $venta_id }}<br>
    --------------------------------<br>
    Total crédito: Gs. {{ number_format($total_original, 0, ',', '.') }}<br>
    Pagado:        Gs. {{ number_format($total_pagado, 0, ',', '.') }}<br>
    Pendiente:     Gs. {{ number_format($saldo, 0, ',', '.') }}<br>

    <div class="separator"></div>

    Pagos realizados:<br>
    @foreach($pagos as $p)
        - {{ $p['fecha'] }} - Gs. {{ number_format($p['monto'], 0, ',', '.') }}<br>
    @endforeach

    <div class="separator"></div>

    Atendido por: {{ $usuario }}<br>

    <div class="separator"></div>
    <br><br>
    Firma del cliente: ___________________________<br><br>

    <div class="separator"></div>
    @include('livewire.tickets.partials.footer', ['defaultFooter' => 'Gracias por su pago'])
   
    <div class="separator"></div>
    @if(!empty($qr_base64))
        <div class="center">
            <img src="data:image/svg+xml;base64,{{ $qr_base64 }}" width="90">
            <div style="font-size: 9px;">Escanea para verificar</div>
        </div>
    @endif

    <div class="separator"></div>
    <div class="center" style="font-size: 10px;">
        Desarrollado por<br><strong>www.crisinfo.com.py</strong>
    </div>

    <div class="separator"></div>
    <div class="center" style="font-size: 9px;">
        Este comprobante fue generado por el sistema.<br>
        Su validez depende de la verificación en el sistema original.
    </div>

</body>
</html>