<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preventa #{{ $previewSale->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            line-height: 1.3;
            width: 80mm;
            margin: 0;
            padding: 0;
            background: white;
        }
        
        .container {
            width: 100%;
            padding: 2mm;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            border-bottom: 1px dashed #333;
        }
        
        .header h1 {
            font-size: 11px;
            margin: 0;
            font-weight: bold;
            line-height: 1.2;
        }
        
        .header p {
            margin: 2px 0;
            font-size: 9px;
        }
        
        .section {
            margin-bottom: 3mm;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 9px;
            background-color: #f0f0f0;
            padding: 2mm 1mm;
            margin-bottom: 2mm;
            border-bottom: 1px solid #333;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
            font-size: 9px;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .info-value {
            text-align: right;
            flex: 1;
            margin-left: 2mm;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
            font-size: 9px;
        }
        
        table thead {
            border-bottom: 1px solid #333;
        }
        
        table thead th {
            padding: 1mm;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
        }
        
        table tbody td {
            padding: 1mm;
            border-bottom: 1px dotted #ccc;
            font-size: 8px;
        }
        
        table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals {
            margin-top: 2mm;
            border-top: 1px dashed #333;
            padding-top: 2mm;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
            font-size: 9px;
            font-weight: bold;
        }
        
        .total-label {
            flex: 1;
        }
        
        .total-value {
            text-align: right;
            min-width: 30mm;
        }
        
        .signature-area {
            margin-top: 5mm;
        }
        
        .signature-box {
            margin-bottom: 3mm;
            border-top: 1px dashed #333;
            padding-top: 2mm;
            text-align: center;
            font-size: 8px;
        }
        
        .signature-box p {
            margin: 1mm 0;
        }
        
        .signature-line {
            margin: 8mm 0 1mm 0;
            min-height: 3mm;
        }
        
        .footer {
            text-align: center;
            margin-top: 3mm;
            padding-top: 2mm;
            border-top: 1px dashed #333;
            font-size: 8px;
            color: #666;
        }

        .badge {
            display: inline-block;
            padding: 1mm 2mm;
            border-radius: 1mm;
            font-size: 7px;
            font-weight: bold;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }

        .badge-success {
            background-color: #28a745;
            color: #fff;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>COMPROBANTE</h1>
            <h1>DE PREVENTA</h1>
            <p style="font-weight: bold;">Preventa #{{ $previewSale->id }}</p>
            <p>{{ $previewSale->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <!-- Datos del Cliente -->
        <div class="section">
            <div class="section-title">CLIENTE</div>
            <div class="info-row">
                <span class="info-label">Nombre:</span>
                <span class="info-value">{{ optional($previewSale->customer)->display_name }}</span>
            </div>
            @if($previewSale->customer->full_document)
                <div class="info-row">
                    <span class="info-label">{{ $previewSale->customer->dv ? 'RUC:' : 'Cédula:' }}</span>
                    <span class="info-value">{{ $previewSale->customer->full_document }}</span>
                </div>
            @endif
            @if($previewSale->customer->phone)
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value">{{ $previewSale->customer->phone }}</span>
                </div>
            @endif
        </div>

        <!-- Datos de la Preventa -->
        <div class="section">
            <div class="section-title">INFORMACIÓN</div>
            <div class="info-row">
                <span class="info-label">Estado:</span>
                <span class="info-value">
                    @if($previewSale->status == 'pending')
                        <span class="badge badge-warning">PENDIENTE</span>
                    @elseif($previewSale->status == 'confirmed')
                        <span class="badge badge-success">CONFIRMADA</span>
                    @else
                        <span class="badge">CANCELADA</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Entrega:</span>
                <span class="info-value">{{ $previewSale->expected_delivery_date->format('d/m/Y') }}</span>
            </div>
        </div>

        <!-- Productos -->
        <div class="section">
            <div class="section-title">PRODUCTOS</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">DESCRIPCIÓN</th>
                        <th style="width: 15%; text-align: center;">CANT.</th>
                        <th style="width: 20%; text-align: right;">P.U.</th>
                        <th style="width: 25%; text-align: right;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($previewSale->details as $detail)
                        <tr>
                            <td>{{ substr($detail->product->name, 0, 15) }}</td>
                            <td class="text-center">{{ Num::qty($detail->quantity) }}</td>
                            <td class="text-right">{{ number_format($detail->price, 0) }}</td>
                            <td class="text-right">{{ number_format($detail->quantity * $detail->price, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totales -->
        <div class="totals">
            <div class="total-row">
                <span class="total-label">TOTAL:</span>
                <span class="total-value">{{ number_format($previewSale->total, 0) }} Gs.</span>
            </div>
        </div>

        <!-- Notas -->
        @if($previewSale->notes)
            <div class="section">
                <div class="section-title">NOTAS</div>
                <p style="font-size: 8px; margin: 2mm 0;">{{ substr($previewSale->notes, 0, 60) }}</p>
            </div>
        @endif

        <!-- Área de Firma -->
        <div class="signature-area">
            <div class="signature-box">
                <p style="margin: 1mm 0;">FIRMA CLIENTE</p>
                <div class="signature-line"></div>
                <p style="margin: 0; font-size: 7px;">Nombre y cédula</p>
            </div>
            <div class="signature-box">
                <p style="margin: 1mm 0;">FIRMA VENDEDOR</p>
                <div class="signature-line"></div>
                <p style="margin: 0; font-size: 7px;">{{ substr($previewSale->createdByUser->name, 0, 15) }}</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Los productos se entregarán</p>
            <p>en la fecha acordada</p>
            <p style="margin-top: 1mm;">{{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>

