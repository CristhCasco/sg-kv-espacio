<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Corte de Caja</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 24mm 18mm 18mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            width: 100%;
            padding: 0;
        }

        .container {
            width: 94%;
            max-width: 94%;
            margin: 0 auto;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding: 0 2mm 10px 2mm;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            color: #666;
        }

        .section {
            margin-bottom: 20px;
            padding: 0 1mm;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 5px 8px;
            margin-bottom: 8px;
            border-left: 4px solid #333;
            text-align: center;
        }

        .row {
            display: table;
            width: 100%;
            table-layout: fixed;
            padding: 6px 4px;
            border-bottom: 1px solid #e0e0e0;
        }

        .row.highlight {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .label {
            display: table-cell;
            width: 50%;
            text-align: left;
            padding-right: 6px;
            vertical-align: middle;
        }

        .value {
            display: table-cell;
            width: 50%;
            text-align: right;
            font-weight: bold;
            padding-left: 6px;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }

        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 8px auto 0 auto;
        }

        table th {
            background-color: #333;
            color: white;
            padding: 7px 8px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            word-break: break-word;
        }

        table td {
            padding: 6px 8px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
            font-size: 9px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #e0e0e0;
            padding: 10px 2mm 0 2mm;
        }

        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            margin-right: 5px;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-info {
            background-color: #023b58;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <h1>CORTE DE CAJA - {{ strtoupper($type) }}</h1>
            <p>Período: {{ $fromDate }} a {{ $toDate }}</p>
            <p>Filtros Aplicados:</p>
            <p style="font-size: 10px;">
                Usuario: <strong>{{ $userName }}</strong> | 
                @if($type == 'Ventas' || $type == 'Compras')
                    Tipo de Pago: <strong>{{ $paymentType }}</strong> | 
                    Método: <strong>{{ $paymentMethod }}</strong>
                @endif
            </p>
            <p style="font-size: 9px;">Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>

        <!-- Resumen de Ventas -->
        @if($type == 'General' || $type == 'Ventas')
        <div class="section">
            <div class="section-title">RESUMEN DE VENTAS</div>
            <div class="row highlight">
                <div class="label">Total Ventas</div>
                <div class="value">{{ number_format($totalSales, 0) }} Gs.</div>
            </div>
            <div class="row">
                <div class="label">Artículos Vendidos</div>
                <div class="value">{{ Num::qty($itemsSales) }}</div>
            </div>
            <div class="row">
                <div class="label">Total Contado</div>
                <div class="value">{{ number_format($totalSalesCash, 0) }} Gs. ({{ ($totalSales > 0) ? number_format(($totalSalesCash / $totalSales) * 100, 1) : 0 }}%)</div>
            </div>
            <div class="row">
                <div class="label">Total Crédito</div>
                <div class="value">{{ number_format($totalSalesCredit, 0) }} Gs. ({{ ($totalSales > 0) ? number_format(($totalSalesCredit / $totalSales) * 100, 1) : 0 }}%)</div>
            </div>
            <div class="row">
                <div class="label">Crédito Pagado</div>
                <div class="value">{{ number_format($totalCreditPaid, 0) }} Gs.</div>
            </div>
            <div class="row">
                <div class="label">Crédito Pendiente</div>
                <div class="value">{{ number_format($totalCreditPending, 0) }} Gs.</div>
            </div>
        </div>

        <!-- Desglose por Método de Pago (Ventas) -->
        @if(!empty($salesByPaymentMethod))
        <div class="section">
            <div class="section-title">DESGLOSE POR MÉTODO DE PAGO (VENTAS)</div>
            <table>
                <thead>
                    <tr>
                        <th>Método de Pago</th>
                        <th>Contado</th>
                        <th>Crédito Pagado</th>
                        <th>Total Cobrado</th>
                        <th>Crédito Pendiente</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesByPaymentMethod as $method => $data)
                    <tr>
                        <td>{{ $method }}</td>
                        <td>{{ number_format($data['contado'], 0) }}</td>
                        <td>{{ number_format($data['credito_pagado'], 0) }}</td>
                        <td><strong>{{ number_format($data['total'], 0) }}</strong></td>
                        <td>{{ number_format($data['credito_pendiente'], 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">No hay datos</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
        @endif

        <!-- Resumen de Compras -->
        @if($type == 'General' || $type == 'Compras')
        <div class="section">
            <div class="section-title">RESUMEN DE COMPRAS</div>
            <div class="row highlight">
                <div class="label">Total Compras</div>
                <div class="value">{{ number_format($totalPurchases, 0) }} Gs.</div>
            </div>
            <div class="row">
                <div class="label">Artículos Comprados</div>
                <div class="value">{{ Num::qty($itemsPurchases) }}</div>
            </div>
            <div class="row">
                <div class="label">Total Contado</div>
                <div class="value">{{ number_format($totalPurchasesCash, 0) }} Gs. ({{ ($totalPurchases > 0) ? number_format(($totalPurchasesCash / $totalPurchases) * 100, 1) : 0 }}%)</div>
            </div>
            <div class="row">
                <div class="label">Total Crédito</div>
                <div class="value">{{ number_format($totalPurchasesCredit, 0) }} Gs. ({{ ($totalPurchases > 0) ? number_format(($totalPurchasesCredit / $totalPurchases) * 100, 1) : 0 }}%)</div>
            </div>
            <div class="row">
                <div class="label">Crédito Pagado</div>
                <div class="value">{{ number_format($totalCreditPurchasePaid, 0) }} Gs.</div>
            </div>
            <div class="row">
                <div class="label">Crédito Pendiente</div>
                <div class="value">{{ number_format($totalCreditPurchasePending, 0) }} Gs.</div>
            </div>
        </div>

        <!-- Desglose por Método de Pago (Compras) -->
        @if(!empty($purchasesByPaymentMethod))
        <div class="section">
            <div class="section-title">DESGLOSE POR MÉTODO DE PAGO (COMPRAS)</div>
            <table>
                <thead>
                    <tr>
                        <th>Método de Pago</th>
                        <th>Contado</th>
                        <th>Crédito Pagado</th>
                        <th>Total Pagado</th>
                        <th>Crédito Pendiente</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchasesByPaymentMethod as $method => $data)
                    <tr>
                        <td>{{ $method }}</td>
                        <td>{{ number_format($data['contado'], 0) }}</td>
                        <td>{{ number_format($data['credito_pagado'], 0) }}</td>
                        <td><strong>{{ number_format($data['total'], 0) }}</strong></td>
                        <td>{{ number_format($data['credito_pendiente'], 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">No hay datos</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
        @endif

        <!-- Gastos Extras (si es General) -->
        @if($type == 'General')
        <div class="section">
            <div class="section-title">GASTOS EXTRAS</div>
            <div class="row highlight">
                <div class="label">Total Gastos</div>
                <div class="value">{{ number_format($totalExtraExpenses, 0) }} Gs.</div>
            </div>
        </div>
        @endif

        <!-- Utilidades (General o Ventas) -->
        @if(($type == 'General' || $type == 'Utilidades') && isset($utilityGross))
        <div class="section">
            <div class="section-title">UTILIDADES (VENTAS - COSTO DE LO VENDIDO)</div>
            <div class="row">
                <div class="label">Ventas con Costo</div>
                <div class="value">{{ number_format($utilityRevenue, 0) }} Gs.</div>
            </div>
            <div class="row">
                <div class="label">Costo de lo Vendido</div>
                <div class="value">{{ number_format($utilityCost, 0) }} Gs.</div>
            </div>
            <div class="row">
                <div class="label">Utilidad Bruta ({{ number_format($utilityMargin, 1) }}%)</div>
                <div class="value">{{ number_format($utilityGross, 0) }} Gs.</div>
            </div>
            <div class="row">
                <div class="label">Gastos Extras</div>
                <div class="value">{{ number_format($totalExtraExpenses, 0) }} Gs.</div>
            </div>
            <div class="row highlight">
                <div class="label">Utilidad Neta (- Gastos Extras)</div>
                <div class="value">{{ number_format($utilityGross - $totalExtraExpenses, 0) }} Gs.</div>
            </div>
            @if($utilityUncostedProducts > 0)
            <div class="row">
                <div class="label">Ventas sin costo (excluidas, {{ $utilityUncostedProducts }} producto(s))</div>
                <div class="value">{{ number_format($utilityUncostedRevenue, 0) }} Gs.</div>
            </div>
            @endif
        </div>
        @endif

        @if($type == 'Utilidades' && !empty($utilityProducts))
        <div class="section">
            <div class="section-title">UTILIDAD POR PRODUCTO</div>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Ventas</th>
                        <th>Costo</th>
                        <th>Utilidad</th>
                        <th>Margen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($utilityProducts as $row)
                    <tr>
                        <td>{{ $row['product'] }}</td>
                        <td>{{ Num::qty($row['quantity']) }}</td>
                        <td>{{ number_format($row['revenue'], 0) }}</td>
                        <td>{{ number_format($row['cost'], 0) }}</td>
                        <td>{{ number_format($row['utility'], 0) }}</td>
                        <td>{{ number_format($row['margin'], 1) }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Pie de página -->
        <div class="footer">
            <p>Este documento fue generado automáticamente por el sistema de Corte de Caja.</p>
            <p>Para más información, contacte al administrador.</p>
        </div>
    </div>
</body>
</html>
