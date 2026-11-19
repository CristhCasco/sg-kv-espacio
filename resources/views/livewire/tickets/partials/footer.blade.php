{{-- Pie del ticket configurable (Configuración → Impresión). Variable opcional: $defaultFooter --}}
@php
    $footerLines = !empty($company)
        ? $company->ticketFooterLines($defaultFooter ?? 'Gracias por su compra')
        : [$defaultFooter ?? 'Gracias por su compra'];
@endphp
@foreach($footerLines as $line)
    <div class="center">{{ $line }}</div>
@endforeach
