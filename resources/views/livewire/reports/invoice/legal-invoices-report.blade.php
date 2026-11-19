<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12">
            <div class="widget">
                <div class="widget-heading">
                    <div>
                        <h4 class="card-title"><b>REPORTE FACTURAS LEGALES / LIBRO IVA VENTAS</b></h4>
                    </div>
                    <div>
                        <a href="{{ route('reports') }}"><i class="fas fa-hand-point-left"></i> Regresar</a>
                    </div>
                </div>

                <div class="widget-content">
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-3">
                            <label class="text-muted">Desde</label>
                            <input type="date" wire:model="dateFrom" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted">Hasta</label>
                            <input type="date" wire:model="dateTo" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button wire:click="loadSales" class="btn btn-dark w-100">Filtrar</button>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <a href="{{ route('reports.legal-invoices.export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                               class="btn btn-success w-100">Exportar Excel</a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped text-center">
                            <thead class="text-white" style="background: #620408">
                                <tr>
                                    <th class="text-white">Fecha</th>
                                    <th class="text-white">Factura</th>
                                    <th class="text-white">Cliente</th>
                                    <th class="text-white">Grav. 10%</th>
                                    <th class="text-white">Grav. 5%</th>
                                    <th class="text-white">Exentas</th>
                                    <th class="text-white">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $s)
                                    <tr>
                                        <td>{{ $s->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $s->invoice_number }}</td>
                                        <td>{{ $s->customer->company ?? $s->customer->name }}</td>
                                        <td>{{ number_format($s->total_iva10, 0, ',', '.') }}</td>
                                        <td>{{ number_format($s->total_iva5, 0, ',', '.') }}</td>
                                        <td>{{ number_format($s->total_exentas, 0, ',', '.') }}</td>
                                        <td><b>{{ number_format($s->total, 0, ',', '.') }}</b></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7">Sin resultados</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
