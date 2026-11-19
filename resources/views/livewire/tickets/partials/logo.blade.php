{{-- Logo de la empresa (Configuración → Impresión). Se incrusta en base64 para que funcione en HTML y en DomPDF. --}}
@php $ticketLogo = optional($company ?? null)->ticketLogo(); @endphp
@if($ticketLogo)
    <div style="text-align: center; margin-bottom: 4px;">
        <img src="{{ $ticketLogo }}" alt="Logo" style="max-width: 70%; max-height: 70px;">
    </div>
@endif
