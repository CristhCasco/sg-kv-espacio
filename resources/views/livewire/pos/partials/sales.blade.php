<div class="row">
    <div class="col-sm-12">
        <div>
            <div class="connect-sorting">
                <!-- Ventas Recientes -->
                <div class="card mt-3">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-white text-center w-100">Ventas Recientes</h6>
                        <button id="toggleSalesBtn" class="btn btn-light btn-sm" onclick="toggleSales()">Mostrar</button>
                    </div>

                    <!-- Lista de Ventas -->
                    <div class="card-body p-2" id="sales-list" style="display: none; visibility: hidden;">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                               <thead style="background-color:rgb(255, 255, 255); color: white;">
                                    <tr>
                                        <th>ID</th>
                                        <th>Total</th>
                                        <th>Cliente</th>
                                        <th>Tipo Pago</th>
                                        <th>Método Pago</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sales as $sale)
                                    <tr>
                                        <td>{{ $sale->id }}</td>
                                        <td>{{ number_format($sale->total, 0) }} Gs.</td>
                                        <td>{{ $sale->customer->name ?? 'Cliente Ocasional' }}</td>
                                        <td>{{ $sale->payment_type }}</td>
                                        <td>{{ $sale->payment_method }}</td>
                                        <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('pos.ticket', base64_encode($sale->id)) }}" class="btn btn-sm btn-dark" onclick="return printTicketFromList(event, this)">
                                                <i class="fas fa-print"></i>Ticket
                                            </a>

                                            @if($sale->customer && strtolower(trim($sale->customer->name)) !== 'ocasional')
                                                <a href="{{ route('invoice.legal', base64_encode($sale->id)) }}" class="btn btn-sm btn-primary mt-1" onclick="return printTicketFromList(event, this)">
                                                    <i class="fas fa-file-invoice"></i>Factura
                                                </a>
                                            @endif

                                            <!-- OPCION PARA ENVIAR POR WHATSAPP - DESHABILITADA TEMPORALMENTE 
                                            @if($sale->customer && $sale->customer->phone)
                                                @php
                                                    $nombre = $sale->customer->name;
                                                    $url = route('pos.ticket', base64_encode($sale->id));
                                                    $mensaje = "Hola {$nombre}! 👋 Te enviamos tu comprobante de venta:\n{$url}";
                                                    $numero = $sale->customer->phone;
                                                    $whatsappURL = "https://wa.me/{$numero}?text=" . urlencode($mensaje);
                                                @endphp

                                                <a href="{{ $whatsappURL }}" class="btn btn-sm btn-success mt-1" target="_blank">
                                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                                </a>
                                            @else
                                                <div class="text-muted small mt-1">Sin número</div>
                                            @endif -->
                                        </td>
                                    </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Fin de Lista de Ventas -->
                </div>
                <!-- Fin de Ventas Recientes -->
            </div>
        </div>
    </div>
</div>

<script>
    function printTicketFromList(event, link) {
        event.preventDefault();

        if (typeof silentPrintPdf === 'function') {
            silentPrintPdf(link.href);
            return false;
        }

        // Fallback if the shared print helper is not available.
        window.location.href = link.href;
        return false;
    }

    function toggleSales() {
        const salesList = document.getElementById('sales-list');
        const toggleButton = document.getElementById('toggleSalesBtn');

        const isHidden = salesList.style.display === 'none';

        if (isHidden) {
            salesList.style.display = 'block';
            salesList.style.visibility = 'visible';
            toggleButton.textContent = 'Ocultar';
        } else {
            salesList.style.display = 'none';
            salesList.style.visibility = 'hidden';
            toggleButton.textContent = 'Mostrar';
        }
    }
</script>
