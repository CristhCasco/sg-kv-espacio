@php $tw = \App\Models\Company::ticketWidth(); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: {{ $tw }}mm auto;
            margin: 0;
        }
        html {
            height: fit-content;
        }
        html, body {
            font-family: monospace;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            width: {{ $tw }}mm;
            max-width: {{ $tw }}mm;
            margin: 0;
            padding: 2mm;
            box-sizing: border-box;
            height: auto;
        }
        * {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        body {
            page-break-after: avoid !important;
            break-after: avoid !important;
        }
        .center { text-align: center; }
        .separator {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .bold { font-weight: bold; }
        .discount {
            font-size: 10px;
            color: #000 !important;
        }
        .right { text-align: right; }

        /* Detalle en columnas, igual que la factura; mismo tamaño de letra que el resto (como PrinterNinja) */
        table.items { width: 100%; border-collapse: collapse; font-size: inherit; }
        table.items th, table.items td { padding: 2px 0; vertical-align: top; }
        table.items th { text-align: left; }
        table.items th.right, table.items td.right { text-align: right; }
        table.items tr.discount td { font-size: inherit; }
        table.items th:first-child, table.items td:first-child { padding-right: 6px; }

        /* Changed: make company name bigger and uppercase */
        .company-name {
            font-size: 14px;          /* increase size (adjust as needed) */
            font-weight: bold;
            text-transform: uppercase;/* ensure uppercase */
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

   <!-- ENCABEZADO: usa $company pasado desde el controlador -->
    @include('livewire.tickets.partials.logo')
    <div class="center bold">
        <div class="company-name">{{ strtoupper($company->name ?? 'N/A') }}</div>
        {{ $company->address ?? 'N/A' }}<br>
        @if(!empty($company->ruc))
            RUC: {{ $company->ruc }}-{{ $company->dv}}<br>
        @endif
        @if(!empty($company->phone))
            Tel: {{ $company->phone }}<br>
        @endif
    </div>

    <div class="separator"></div>

    <!-- DATOS VENTA -->
    <div class="center bold">COMPROBANTE DE VENTA</div>
    <div class="center">{{ $date }}</div>

    <div class="separator"></div>

    Cliente: {{ $customer }}<br>
    Venta: #{{ $sale_id }}<br>
    Tipo: {{ strtoupper($payment_type) }}

    <div class="separator"></div>

    <!-- DETALLE DE PRODUCTOS: CANT | DESCRIPCIÓN | P.U. | SUBT (igual que la factura) -->
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
            @foreach($products as $item)
                <tr>
                    <td>{{ $item['is_weighted'] ? Num::qty($item['quantity']) : number_format($item['quantity'], 0, ',', '.') }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="right">{{ number_format($item['final_price'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                </tr>
                @if(isset($item['unit_discount']) && $item['unit_discount'] > 0)
                    <tr class="discount">
                        <td></td>
                        <td colspan="2">Descuento unitario</td>
                        <td class="right">-{{ number_format($item['unit_discount'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="discount">
                        <td></td>
                        <td colspan="2">Total descuento</td>
                        <td class="right">-{{ number_format($item['total_discount'], 0, ',', '.') }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="separator"></div>

    <!-- RESUMEN FINAL -->
    Ítems: {{ Num::qty($total_items) }}<br>

    @if(isset($total_savings) && $total_savings > 0)
        Total sin descuento: Gs. {{ number_format($total_without_discount, 0, ',', '.') }}<br>
        Ahorro total: Gs. {{ number_format($total_savings, 0, ',', '.') }}<br>
        Total a pagar: Gs. {{ number_format($total, 0, ',', '.') }}<br>
    @else
        Total: Gs. {{ number_format($total, 0, ',', '.') }}<br>
    @endif

    @if(strtoupper($payment_type) === 'CREDITO')
        Anticipo: Gs. {{ number_format($cash, 0, ',', '.') }}<br>
        Saldo pendiente: Gs. {{ number_format($pending_balance, 0, ',', '.') }}<br>
        Estado: {{ ($pending_balance ?? 0) <= 0 ? 'PAGADO' : 'PENDIENTE' }}<br>
    @else
        Pagado: Gs. {{ number_format($cash, 0, ',', '.') }}<br>
        @if(isset($change) && $change > 0)
            Vuelto: Gs. {{ number_format($change, 0, ',', '.') }}<br>
        @endif
    @endif

    <div class="separator"></div>

    Atendido por: {{ auth()->user()->name ?? 'Sistema' }}

    <!-- <div class="separator"></div> -->

    <!-- @if(isset($qr_base64))
        <div class="center">
            <img src="data:image/svg+xml;base64,{{ $qr_base64 }}" width="100">
            <br>
            <small>Escanea el QR para verificar</small>
        </div>
    @endif -->

    <div class="separator"></div>
    <!-- FIRMA PROMOCIONAL -->
    <div class="center" style="font-size: 10px;">
       @include('livewire.tickets.partials.footer')
       <strong>www.crisinfo.com.py</strong>
    </div>

    <!-- <div class="separator"></div>
    <div class="center" style="font-size: 9px;">
        Este comprobante fue generado por el sistema.<br>
        Su validez depende de la verificación en el sistema original.
    </div> -->

   

    @if(isset($auto_print) && $auto_print)
    <script>
        // Este ticket se imprime solo: sgPrint (modo navegador) no debe volver a imprimirlo
        window.SG_SELF_PRINT = true;

        function getContentHeight() {
            return Math.max(
                document.body.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.scrollHeight,
                document.documentElement.offsetHeight
            );
        }

        function applyPageSize() {
            var h = getContentHeight() + 20;
            var el = document.getElementById('dyn-page-size');
            if (!el) {
                el = document.createElement('style');
                el.id = 'dyn-page-size';
                document.head.appendChild(el);
            }
            el.textContent = '@page { size: {{ $tw }}mm ' + h + 'px; margin: 0; }';
        }

        // Actualizar el tamaño justo antes de que Chrome genere el layout de impresión
        window.addEventListener('beforeprint', applyPageSize);

        window.onload = function () {
            // Esperar 2 ciclos de renderizado para medir el DOM completamente pintado
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    applyPageSize();
                    window.print();
                });
            });
        };
    </script>
    @endif

</body>
</html>