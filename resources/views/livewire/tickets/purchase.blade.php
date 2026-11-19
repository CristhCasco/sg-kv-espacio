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
            width: {{ round($tw * 72 / 25.4, 2) }}px;
            margin: 0;
            padding: 0;
        }
        .center {
            text-align: center;
        }
        .right {
            text-align: right;
        }
        .separator {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .discount {
            color: #000 !important;
            font-size: 10px;
        }

        /* Detalle en columnas, igual que la factura; mismo tamaño de letra que el resto (como PrinterNinja) */
        table.items { width: 100%; border-collapse: collapse; font-size: inherit; }
        table.items th, table.items td { padding: 2px 0; vertical-align: top; }
        table.items th { text-align: left; }
        table.items th.right, table.items td.right { text-align: right; }
        table.items tr.discount td { font-size: inherit; }
        table.items th:first-child, table.items td:first-child { padding-right: 6px; }
    </style>
</head>
<body>

    @include('livewire.tickets.partials.logo')
    <div class="center">
        <strong>{{ $empresa['name'] }}</strong><br>
        @if(!empty($empresa['ruc']))
        RUC: {{ $empresa['ruc'] }}-{{$empresa['dv']}}<br>
        @endif
        Tel: {{ $empresa['phone'] }}
    </div>

    <div class="separator"></div>

    <div class="center">
        <strong>COMPROBANTE DE COMPRA</strong><br>
        {{ $fecha }}
    </div>

    <div class="separator"></div>

    Proveedor: {{ $proveedor }}<br>
    Compra: #{{ $compra_id }}<br>
    Tipo: {{ strtoupper($tipo_pago) }}<br>

    <div class="separator"></div>

    <!-- DETALLE: CANT | DESCRIPCIÓN | P.U. | SUBT (igual que la factura) -->
    <table class="items">
        <thead>
            <tr>
                <th>Cant</th>
                <th>Descripción</th>
                <th class="right">P.U.</th>
                <th class="right">SubT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $item)
                <tr>
                    <td>{{ Num::qty($item['cantidad']) }}</td>
                    <td>{{ $item['nombre'] }}</td>
                    <td class="right">{{ number_format($item['precio_base'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($item['subtotal_base'], 0, ',', '.') }}</td>
                </tr>
                @if($item['descuento_unit'] > 0)
                    <tr class="discount">
                        <td></td>
                        <td colspan="2">Descuento unitario</td>
                        <td class="right">-{{ number_format($item['descuento_unit'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="discount">
                        <td></td>
                        <td colspan="2">Total descuento</td>
                        <td class="right">-{{ number_format($item['descuento_total'], 0, ',', '.') }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="separator"></div>

    Ítems: {{ Num::qty($total_items) }}<br>
    
    @if($ahorro_total > 0)
        Total sin descuento: Gs. {{ number_format($total_sin_descuento, 0, ',', '.') }}<br>
        <span class="discount">Ahorro total: Gs. {{ number_format($ahorro_total, 0, ',', '.') }}</span><br>
        Total a pagar: Gs. {{ number_format($total, 0, ',', '.') }}<br>
    @else
        Total: Gs. {{ number_format($total, 0, ',', '.') }}<br>
    @endif


    @if($tipo_pago === 'CREDITO')
        Anticipo: Gs. {{ number_format($efectivo, 0, ',', '.') }}<br>
        Saldo pendiente: Gs. {{ number_format($saldo_pendiente, 0, ',', '.') }}<br>
        Estado: {{ $saldo_pendiente <= 0 ? 'PAGADO' : 'PENDIENTE' }}<br>
    @else
        Pagado: Gs. {{ number_format($efectivo, 0, ',', '.') }}<br>
        @if($vuelto > 0)
            Vuelto: Gs. {{ number_format($vuelto, 0, ',', '.') }}<br>
        @endif
    @endif

    <div class="separator"></div>
    Atendido por: {{ auth()->user()->name }}

    <div class="separator"></div>
    @include('livewire.tickets.partials.footer')
    <div class="center" style="font-size: 10px;">
        Desarrollado por<br>
        <strong>www.crisinfo.com.py</strong>
    </div>

</body>
</html>
