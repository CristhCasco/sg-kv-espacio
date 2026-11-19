<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12">
            <div class="widget">
                <div class="widget-heading d-flex justify-content-between align-items-center">
                    <h4 class="card-title"><b>{{ $componentName }}</b></h4>
                    <a href="{{ route('reports') }}"><i class="fas fa-hand-point-left"></i> Regresar</a>
                </div>

                <div class="widget-content">
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-2">
                            <label class="text-muted">Usuario</label>
                            <select wire:model="userId" class="form-control">
                                <option value="0">Todos</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted">Estado</label>
                            <select wire:model="statusFilter" class="form-control">
                                <option value="ALL">Todos</option>
                                <option value="PAGADO">Pagado</option>
                                <option value="PENDIENTE">Pendiente</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted">Tipo de Reporte</label>
                            <select wire:model="reportType" class="form-control">
                                <option value="0">Ventas del día</option>
                                <option value="1">Ventas por fecha</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted">Desde</label>
                            <input type="date"
                                wire:model="dateFrom"
                                class="form-control flatpickr"
                                placeholder="Desde"
                                {{ $reportType == 0 ? 'disabled' : '' }}>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted">Hasta</label>
                            <input type="date"
                                wire:model="dateTo"
                                class="form-control flatpickr"
                                placeholder="Hasta"
                                {{ $reportType == 0 ? 'disabled' : '' }}>
                        </div>

                        <div class="col-md-2 d-flex gap-1">
                            <button wire:click="$refresh" class="btn btn-dark w-100">Consultar</button>
                            <!-- <a class="btn btn-secondary w-100 {{ $data->count() < 1 ? 'disabled' : '' }}"
                                href="{{ url('report/pdf/' . $userId . '/' . $reportType . '/' . $dateFrom . '/' . $dateTo) }}"
                                target="_blank">PDF</a> -->
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-bordered mt-1" style="border-color:#620408;">
                                <thead style="background-color:#620408 !important; color:white !important; border-bottom:3px solid #4b0303;">
                                    <tr class="text-center">
                                        <th style="color:white !important;">CLIENTE</th>
                                        <th style="color:white !important;">TIPO DOC</th>
                                        <th style="color:white !important;">N° FACTURA</th>
                                        <th style="color:white !important;">TOTAL</th>
                                        <th style="color:white !important;">ITEMS</th>
                                        <th style="color:white !important;">ESTADO PAGO</th>
                                        <th style="color:white !important;">ESTADO DOC</th>
                                        <th style="color:white !important;">USUARIO</th>
                                        <th style="color:white !important;">CANCELADO POR</th>
                                        <th style="color:white !important;">FECHA</th>
                                        <th style="color:white !important;">ACCIÓN</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($data as $d)
                                        @php
                                            $isCancelled = strtoupper($d->document_status ?? '') === 'ANULADA';
                                            $cancelledAt = isset($d->cancelled_at)
                                                ? \Carbon\Carbon::parse($d->cancelled_at)->format('d/m/Y H:i')
                                                : null;
                                        @endphp

                                        <tr style="{{ $isCancelled ? 'background-color:#ffcccc; color:#7a0000;' : 'background-color:white; color:black;' }}">
                                            <td class="text-center">{{ $d->customer }}</td>
                                            <td class="text-center">{{ $d->invoice_type_name ?? 'Ticket' }}</td>
                                            <td class="text-center">{{ $d->invoice_number ?? '-' }}</td>
                                            <td class="text-center">{{ number_format($d->total, 0, ',', '.') }} Gs.</td>
                                            <td class="text-center">{{ Num::qty($d->items) }}</td>
                                            <td class="text-center">{{ $d->status }}</td>

                                            <td class="text-center">
                                                @if($isCancelled)
                                                    <span class="badge bg-danger"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Anulada el {{ $cancelledAt ?? 'sin registro' }}">
                                                        Anulada
                                                    </span>
                                                @else
                                                    <span class="badge bg-success">{{ $d->document_status ?? 'ACTIVA' }}</span>
                                                @endif
                                            </td>

                                            <td class="text-center">{{ $d->usuario }}</td>
                                            <td class="text-center">{{ $d->usuario_cancelo ?? '-' }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($d->created_at)->format('d-m-Y H:i') }}</td>

                                            <td class="text-center" width="50px">
                                                <button wire:click.prevent="getDetails({{ $d->id }})"
                                                        class="btn btn-dark btn-sm"
                                                        @if($isCancelled) disabled @endif>
                                                    <i class="fas fa-list"></i>
                                                </button>

                                                <button wire:click="cancelSale({{ $d->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="cancelSale({{ $d->id }})"
                                                        class="btn btn-sm btn-danger"
                                                        @if($isCancelled) disabled @endif>
                                                    <span wire:loading.remove wire:target="cancelSale({{ $d->id }})">
                                                        <i class="fas fa-ban"></i> Anular
                                                    </span>
                                                    <span wire:loading wire:target="cancelSale({{ $d->id }})">
                                                        <i class="fas fa-spinner fa-spin"></i> Procesando...
                                                    </span>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center">Sin resultados</td>
                                        </tr>
                                    @endforelse
                                </tbody>

                               @if($data->count() > 0)
                                    @php
                                        $total = $totals->sum('total');
                                        $items = $totals->sum('items');
                                    @endphp
                                    <tfoot style="background-color:#f9f9f9;">
                                        <tr class="text-center">
                                            <td colspan="3"><strong>TOTAL:</strong></td>
                                            <td><h4><strong>{{ number_format($total, 0, ',', '.') }} Gs.</strong></h4></td>
                                            <td><h4>{{ Num::qty($items) }}</h4></td>
                                            <td colspan="6"></td>
                                        </tr>
                                    </tfoot>
                                @endif

                            </table>


                            <div class="mt-3 d-flex justify-content-center">
                                {{ $data->links() }}
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('livewire.reports.sales.sales-detail')
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr(document.getElementsByClassName('flatpickr'), {
                enableTime: false,
                dateFormat: 'Y-m-d',
                locale: {
                    firstDayofWeek: 1,
                    weekdays: {
                        shorthand: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"],
                        longhand: ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"],
                    },
                    months: {
                        shorthand: ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"],
                        longhand: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
                    },
                }
            });

            window.livewire.on('show-modal', () => {
                $('#modalDetails').modal('show');
            });
        });

        window.addEventListener('confirm-delete-sale', () => {
            if (confirm('¿Estás seguro que deseas eliminar esta venta?')) {
                @this.call('deleteSale');
            }
        });

        window.addEventListener('sale-cannot-delete', () => {
            noty('No se puede eliminar una venta con estado PENDIENTE. Márcala como pagada primero.', 2);
        });


    
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializa tooltips de Bootstrap (5+)
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

    </script>
</div>
