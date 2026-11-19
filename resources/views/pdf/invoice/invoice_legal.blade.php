@php $tw = \App\Models\Company::ticketWidth(); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura</title>
<style>
  @page { size: {{ $tw }}mm auto; margin: 0; }
  html {
    height: fit-content;
  }
  html, body {
    width: {{ $tw }}mm;
    max-width: {{ $tw }}mm;
    margin: 0;
    padding: 2mm;
    box-sizing: border-box;
    font-family: monospace;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
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
  .right { text-align: right; }
  .bold { font-weight: bold; }
  .separator { border-top: 1px dashed #000; margin: 4px 0; }
  table { width: 100%; border-collapse: collapse; font-size: 9px; }
  th, td { padding: 2px 0; }
</style>
</head>
<body>

  <!-- Encabezado Empresa -->
  @include('livewire.tickets.partials.logo')
  <div class="center" style="line-height: 1.3;">
    <span class="bold" style="font-size:15px;">{{ $company->name }}</span><br>
    @if(!empty($company->trade_name) && $company->trade_name !== $company->name)
      <span style="font-size:10px;">{{ $company->trade_name }}</span><br>
    @endif
    @if(!empty($company->economic_activity))
      <span style="font-size:9px;">{{ $company->economic_activity }}</span><br>
    @endif
    <span style="font-size:10px;">{{ $company->address ?? '-' }}{{ !empty($company->city) ? ' - ' . $company->city : '' }}</span><br>
    @if(!empty($company->phone))
      <span style="font-size:10px;">Tel: {{ $company->phone }}</span><br>
    @endif
    <br>
    RUC: {{ $company->ruc }}-{{ $company->dv }}<br>
    Timbrado N°: {{ $company->timbrado_number }}<br>
    @if(!empty($company->timbrado_valid_from))
      Inicio de vigencia: {{ \Carbon\Carbon::parse($company->timbrado_valid_from)->format('d/m/Y') }}<br>
    @endif
    Vencimiento: {{ \Carbon\Carbon::parse($company->timbrado_valid_until)->format('d/m/Y') }}<br>
    <span class="separator"></span>
    <span class="bold">FACTURA</span><br>
    N° {{ $sale->invoice_number ?? '---' }}
  </div>

  <div class="separator"></div>

  <!-- Datos del Cliente -->
  <div style="font-size:9px; line-height: 1.3;">
    <span class="bold">Cliente:</span>

    @if($sale->customer)
        @php
            $tipoPersona = strtoupper(trim($sale->customer->person ?? ''));
        @endphp

        @if($tipoPersona === 'JURIDICA')
            {{ $sale->customer->company ?? '—' }}
        @else
            {{ trim($sale->customer->name . ' ' . ($sale->customer->last_name ?? '')) }}
        @endif
    @else
        Consumidor Final
    @endif

    <br>

    {{-- -------- DOCUMENTO DEL CLIENTE (CORREGIDO) -------- --}}
    <span class="bold">
        {{ $sale->customer ? $sale->customer->document_label : 'C.I.' }}:
    </span>

    <span style="font-family: monospace;">
        {{-- Con RUC (física o jurídica) se imprime número-DV; sin RUC, el C.I. --}}
        {{ optional($sale->customer)->full_document ?? '-' }}
    </span>
    <br>

    <span class="bold">Dirección:</span> {{ $sale->customer->address ?? '-' }}<br>
    <span class="bold">Condición:</span> {{ $sale->payment_type ?? 'CONTADO' }}<br>
    <span class="bold">Fecha:</span> {{ $sale->created_at->format('d/m/Y H:i') }}
  </div>

  <div class="separator"></div>

  <!-- Detalles -->
  <table>
    <thead>
      <tr>
        <th style="text-align:left;">Cant</th>
        <th style="text-align:left;">Descripción</th>
        <th style="text-align:right;">P.U.</th>
        <th style="text-align:right;">SubT</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sale->details as $item)
        @php
            $quantity = $item->quantity ?? 1;
            $productName = $item->product->name ?? 'CONSUMISIÓN';
            $price = $item->price ?? $item->sub_total ?? 0;
            $subtotal = $item->sub_total ?? ($item->price ?? 0) * $quantity;
        @endphp
        <tr>
          <td>{{ Num::qty($quantity) }}</td>
          <td>{{ \Illuminate\Support\Str::limit($productName, 18) }}</td>
          <td class="right">{{ number_format($price, 0, ',', '.') }}</td>
          <td class="right">{{ number_format($subtotal, 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="separator"></div>

  <!-- Totales -->
  {{-- Subtotales con IVA incluido (suman el total) y liquidación del IVA aparte --}}
  @php $t = \App\Services\InvoiceService::totals($sale); @endphp

  <table>
    <tr><td class="left bold" colspan="2">Subtotales (IVA incluido)</td></tr>
    <tr><td class="left">&nbsp; Exentas:</td><td class="right">{{ number_format($t['exentas'], 0, ',', '.') }}</td></tr>
    <tr><td class="left">&nbsp; 5%:</td><td class="right">{{ number_format($t['sub5'], 0, ',', '.') }}</td></tr>
    <tr><td class="left">&nbsp; 10%:</td><td class="right">{{ number_format($t['sub10'], 0, ',', '.') }}</td></tr>
    <tr><td class="left bold">Total a Pagar:</td><td class="right bold">{{ number_format($t['total'], 0, ',', '.') }} Gs.</td></tr>
  </table>

  <div class="separator"></div>

  <table>
    <tr><td class="left bold" colspan="2">Liquidación del IVA</td></tr>
    <tr><td class="left">&nbsp; (5%):</td><td class="right">{{ number_format($t['iva5'], 0, ',', '.') }}</td></tr>
    <tr><td class="left">&nbsp; (10%):</td><td class="right">{{ number_format($t['iva10'], 0, ',', '.') }}</td></tr>
    <tr><td class="left bold">&nbsp; Total IVA:</td><td class="right bold">{{ number_format($t['total_iva'], 0, ',', '.') }}</td></tr>
  </table>

  <div class="separator"></div>


  <!-- Pie -->
  <div class="center" style="font-size:9px;">
    En Letras:<br>
    <span class="bold">
      {{ \Illuminate\Support\Str::upper(\App\Helpers\NumberToWords::convert($sale->total ?? 0)) }} GUARANÍES
    </span>
    <br><br>
    <strong>@include('livewire.tickets.partials.footer')</strong>
    <span>www.crisinfo.com.py</span>
  </div>

    @if(isset($auto_print) && $auto_print)
    <script>
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

      window.addEventListener('beforeprint', applyPageSize);

      window.onload = function () {
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            applyPageSize();
          });
        });
      };
    </script>
    @endif

</body>
</html>
