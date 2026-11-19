<div class="row sales layout-top-spacing arqueo">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">

            <!-- Encabezado -->
            <div class="arq-header">
                <div>
                    <h4 class="arq-title">Arqueo</h4>
                    <span class="arq-subtitle">
                        {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }}
                        @if($fromDate != $toDate) al {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }} @endif
                        · {{ $type }}
                    </span>
                </div>
                <div class="arq-actions">
                    <button wire:click="exportExcel" class="btn btn-sm btn-outline-success" title="Exportar a Excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button wire:click="exportPDF" class="btn btn-sm btn-outline-danger" title="Exportar a PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="arq-filters row">
                <div class="col-sm-6 col-md-2">
                    <label>Tipo</label>
                    <select wire:model="type" wire:change="Consult" class="form-control form-control-sm">
                        <option value="General">General</option>
                        <option value="Ventas">Ventas</option>
                        <option value="Compras">Compras</option>
                        <option value="Utilidades">Utilidades</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label>Usuario</label>
                    <select wire:model="userId" wire:change="Consult" class="form-control form-control-sm">
                        <option value="0">Todos</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label>Desde</label>
                    <input type="date" wire:model="fromDate" wire:change="Consult" class="form-control form-control-sm">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label>Hasta</label>
                    <input type="date" wire:model="toDate" wire:change="Consult" class="form-control form-control-sm">
                </div>
                @if($type == 'Ventas' || $type == 'Compras')
                    <div class="col-sm-6 col-md-2">
                        <label>Tipo de pago</label>
                        <select wire:model="paymentType" wire:change="Consult" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="Contado">Contado</option>
                            <option value="Credito">Crédito</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <label>Método de pago</label>
                        <select wire:model="paymentMethod" wire:change="Consult" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div wire:loading.delay class="arq-loading">Actualizando…</div>

            @if($type == 'General')
                @php $balance = $totalSales - $totalPurchases - $totalExtraExpenses; @endphp

                <!-- Resumen del período -->
                <div class="arq-section">
                    <h6 class="arq-section-title">Resumen del período</h6>
                    <div class="row">
                        <div class="col-sm-6 col-lg-3 mb-3">
                            <div class="arq-card arq-accent-sales">
                                <span class="arq-label">Ventas</span>
                                <span class="arq-value">{{ number_format($totalSales, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">{{ count($sales) }} venta(s) · {{ Num::qty($itemsSales) }} artículo(s)</span>
                                <a href="#" class="arq-link" data-toggle="modal" data-target="#modal-sales-list">Ver ventas →</a>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3 mb-3">
                            <div class="arq-card arq-accent-purchases">
                                <span class="arq-label">Compras</span>
                                <span class="arq-value">{{ number_format($totalPurchases, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">{{ count($purchases) }} compra(s) · {{ Num::qty($itemsPurchases) }} artículo(s)</span>
                                <a href="#" class="arq-link" data-toggle="modal" data-target="#modal-purchases-list">Ver compras →</a>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3 mb-3">
                            <div class="arq-card arq-accent-expenses">
                                <span class="arq-label">Gastos extras</span>
                                <span class="arq-value">{{ number_format($totalExtraExpenses, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">{{ count($extraExpenses) }} gasto(s)</span>
                                <div class="arq-card-actions">
                                    <a href="#" class="arq-link" data-toggle="modal" data-target="#modal-expenses-list">Ver gastos →</a>
                                    <a href="#" class="arq-link arq-link-add" wire:click.prevent="$emit('show-expense-modal')">+ Agregar</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3 mb-3">
                            <div class="arq-card arq-accent-balance">
                                <span class="arq-label">Balance del período</span>
                                <span class="arq-value {{ $balance < 0 ? 'arq-neg' : 'arq-pos' }}">{{ number_format($balance, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">Ventas − compras − gastos</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($type == 'Ventas')
                <!-- Resumen de ventas -->
                <div class="arq-section">
                    <h6 class="arq-section-title">Ventas del período</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="arq-card arq-accent-sales">
                                <span class="arq-label">Total ventas</span>
                                <span class="arq-value">{{ number_format($totalSales, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">{{ count($sales) }} venta(s) · {{ Num::qty($itemsSales) }} artículo(s)</span>
                                <a href="#" class="arq-link" data-toggle="modal" data-target="#modal-sales-list">Ver ventas →</a>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="arq-card arq-accent-pos">
                                <span class="arq-label">Contado</span>
                                <span class="arq-value">{{ number_format($totalSalesCash, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">{{ $totalSales > 0 ? number_format(($totalSalesCash / $totalSales) * 100, 1) : 0 }}% de las ventas</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="arq-card arq-accent-expenses">
                                <span class="arq-label">Crédito</span>
                                <span class="arq-value">{{ number_format($totalSalesCredit, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">Cobrado {{ number_format($totalCreditPaid, 0) }} · <span class="arq-neg">Pendiente {{ number_format($totalCreditPending, 0) }}</span></span>
                            </div>
                        </div>
                    </div>
                    @include('livewire.cashout.partials.payment-methods', [
                        'rows' => $salesByPaymentMethod,
                        'cash' => $totalSalesCash,
                        'creditPaid' => $totalCreditPaid,
                        'creditPending' => $totalCreditPending,
                        'totalLabel' => 'Total cobrado',
                    ])
                </div>
            @endif

            @if($type == 'Compras')
                <!-- Resumen de compras -->
                <div class="arq-section">
                    <h6 class="arq-section-title">Compras del período</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="arq-card arq-accent-purchases">
                                <span class="arq-label">Total compras</span>
                                <span class="arq-value">{{ number_format($totalPurchases, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">{{ count($purchases) }} compra(s) · {{ Num::qty($itemsPurchases) }} artículo(s)</span>
                                <a href="#" class="arq-link" data-toggle="modal" data-target="#modal-purchases-list">Ver compras →</a>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="arq-card arq-accent-pos">
                                <span class="arq-label">Contado</span>
                                <span class="arq-value">{{ number_format($totalPurchasesCash, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">{{ $totalPurchases > 0 ? number_format(($totalPurchasesCash / $totalPurchases) * 100, 1) : 0 }}% de las compras</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="arq-card arq-accent-expenses">
                                <span class="arq-label">Crédito</span>
                                <span class="arq-value">{{ number_format($totalPurchasesCredit, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">Pagado {{ number_format($totalCreditPurchasePaid, 0) }} · <span class="arq-neg">Pendiente {{ number_format($totalCreditPurchasePending, 0) }}</span></span>
                            </div>
                        </div>
                    </div>
                    @include('livewire.cashout.partials.payment-methods', [
                        'rows' => $purchasesByPaymentMethod,
                        'cash' => $totalPurchasesCash,
                        'creditPaid' => $totalCreditPurchasePaid,
                        'creditPending' => $totalCreditPurchasePending,
                        'totalLabel' => 'Total pagado',
                    ])
                </div>
            @endif

            @if($type == 'General')
                @php $utilityNet = $utilityGross - $totalExtraExpenses; @endphp

                <!-- Utilidades: resumen corto -->
                <div class="arq-section">
                    <h6 class="arq-section-title">Utilidades <span>ganancia real de lo vendido</span></h6>
                    <div class="arq-card arq-utility">
                        <div class="row">
                            <div class="col-lg-7 arq-statement">
                                <div class="arq-line">
                                    <span>Ventas con costo</span>
                                    <strong>{{ number_format($utilityRevenue, 0) }} Gs.</strong>
                                </div>
                                <div class="arq-line">
                                    <span>− Costo de lo vendido</span>
                                    <strong>{{ number_format($utilityCost, 0) }} Gs.</strong>
                                </div>
                                <div class="arq-line arq-subtotal">
                                    <span>Utilidad bruta</span>
                                    <strong class="{{ $utilityGross < 0 ? 'arq-neg' : 'arq-pos' }}">{{ number_format($utilityGross, 0) }} Gs.</strong>
                                </div>
                                <div class="arq-line">
                                    <span>− Gastos extras</span>
                                    <strong>{{ number_format($totalExtraExpenses, 0) }} Gs.</strong>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="arq-net">
                                    <span class="arq-label">Utilidad neta</span>
                                    <span class="arq-net-value {{ $utilityNet < 0 ? 'arq-neg' : 'arq-pos' }}">{{ number_format($utilityNet, 0) }} <small>Gs.</small></span>
                                    <span class="arq-hint">Margen bruto {{ number_format($utilityMargin, 1) }}%</span>
                                    <div class="arq-margin">
                                        <div class="arq-margin-bar" style="width: {{ max(0, min(100, $utilityMargin)) }}%"></div>
                                    </div>
                                    @if($utilityUncostedProducts > 0)
                                        <div class="arq-note">
                                            {{ $utilityUncostedProducts }} producto(s) vendido(s) sin costo por {{ number_format($utilityUncostedRevenue, 0) }} Gs. no se incluyen.
                                        </div>
                                    @endif
                                    <a href="#" class="arq-link" wire:click.prevent="viewUtilities">Ver análisis de utilidades →</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($type == 'Utilidades')
                @php $utilityNet = $utilityGross - $totalExtraExpenses; @endphp

                <!-- Utilidades: análisis completo -->
                <div class="arq-section">
                    <h6 class="arq-section-title">Utilidades del período <span>ventas − costo de lo vendido</span></h6>
                    <div class="row">
                        <div class="col-sm-6 col-lg-3 mb-3">
                            <div class="arq-card arq-accent-sales">
                                <span class="arq-label">Ventas con costo</span>
                                <span class="arq-value">{{ number_format($utilityRevenue, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">{{ count($utilityProducts) }} producto(s) vendido(s)</span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3 mb-3">
                            <div class="arq-card arq-accent-purchases">
                                <span class="arq-label">Costo de lo vendido</span>
                                <span class="arq-value">{{ number_format($utilityCost, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">Costo guardado al vender</span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3 mb-3">
                            <div class="arq-card arq-accent-pos">
                                <span class="arq-label">Utilidad bruta</span>
                                <span class="arq-value {{ $utilityGross < 0 ? 'arq-neg' : 'arq-pos' }}">{{ number_format($utilityGross, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">Margen {{ number_format($utilityMargin, 1) }}%</span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3 mb-3">
                            <div class="arq-card arq-accent-balance">
                                <span class="arq-label">Utilidad neta</span>
                                <span class="arq-value {{ $utilityNet < 0 ? 'arq-neg' : 'arq-pos' }}">{{ number_format($utilityNet, 0) }} <small>Gs.</small></span>
                                <span class="arq-hint">Utilidad bruta − gastos extras ({{ number_format($totalExtraExpenses, 0) }})</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <div class="arq-card arq-card-table">
                                <div class="arq-card-head">Por categoría <span>{{ count($utilityByCategory) }}</span></div>
                                <div class="table-responsive arq-scroll">
                                    <table class="table arq-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Categoría</th>
                                                <th class="text-right">Ventas</th>
                                                <th class="text-right">Utilidad</th>
                                                <th class="text-right">Margen</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($utilityByCategory as $row)
                                                <tr>
                                                    <td>{{ $row['category'] }}</td>
                                                    <td class="text-right">{{ number_format($row['revenue'], 0) }}</td>
                                                    <td class="text-right"><strong class="{{ $row['utility'] < 0 ? 'arq-neg' : '' }}">{{ number_format($row['utility'], 0) }}</strong></td>
                                                    <td class="text-right">{{ number_format($row['margin'], 1) }}%</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="arq-empty">Sin ventas con costo en el período</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <div class="arq-card arq-card-table">
                                <div class="arq-card-head">Vendidos sin costo cargado <span>{{ count($utilityUncostedList) }}</span></div>
                                <div class="table-responsive arq-scroll">
                                    <table class="table arq-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th class="text-right">Cant.</th>
                                                <th class="text-right">Ventas excluidas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($utilityUncostedList as $row)
                                                <tr>
                                                    <td>{{ $row['product'] }}</td>
                                                    <td class="text-right">{{ Num::qty($row['quantity']) }}</td>
                                                    <td class="text-right">{{ number_format($row['revenue'], 0) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="arq-empty">Todos los productos vendidos tienen costo</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if(count($utilityUncostedList) > 0)
                                    <div class="arq-note m-3">
                                        Estas ventas no se incluyen en la utilidad. Carga su costo en Productos → "Sin costo cargado".
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="arq-card arq-card-table">
                        <div class="arq-card-head">
                            Utilidad por producto <span>{{ count($utilityProducts) }}</span>
                            <a href="#" class="arq-link arq-head-link" wire:click.prevent="viewLessUtilities">← Ver menos</a>
                        </div>
                        <div class="table-responsive arq-scroll-lg">
                            <table class="table arq-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th class="text-right">Cant.</th>
                                        <th class="text-right">Ventas</th>
                                        <th class="text-right">Costo</th>
                                        <th class="text-right">Utilidad</th>
                                        <th class="text-right">Margen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($utilityProducts as $row)
                                        <tr>
                                            <td>{{ $row['product'] }}</td>
                                            <td>{{ $row['category'] }}</td>
                                            <td class="text-right">{{ Num::qty($row['quantity']) }}</td>
                                            <td class="text-right">{{ number_format($row['revenue'], 0) }}</td>
                                            <td class="text-right">{{ number_format($row['cost'], 0) }}</td>
                                            <td class="text-right"><strong class="{{ $row['utility'] < 0 ? 'arq-neg' : '' }}">{{ number_format($row['utility'], 0) }}</strong></td>
                                            <td class="text-right {{ $row['margin'] < 0 ? 'arq-neg' : '' }}">{{ number_format($row['margin'], 1) }}%</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="arq-empty">Sin ventas con costo en el período</td></tr>
                                    @endforelse
                                </tbody>
                                @if(count($utilityProducts) > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="3">Total</td>
                                            <td class="text-right">{{ number_format($utilityRevenue, 0) }}</td>
                                            <td class="text-right">{{ number_format($utilityCost, 0) }}</td>
                                            <td class="text-right {{ $utilityGross < 0 ? 'arq-neg' : '' }}">{{ number_format($utilityGross, 0) }}</td>
                                            <td class="text-right">{{ number_format($utilityMargin, 1) }}%</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('livewire.cashout.modalDetails')
    @include('livewire.cashout.expenseModal')
    @include('livewire.cashout.partials.list-modals')

    <style>
        .arqueo .widget { padding: 24px; }
        .arqueo .arq-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
        .arqueo .arq-title { margin: 0; font-weight: 700; color: #620408; }
        .arqueo .arq-subtitle { color: #888ea8; font-size: 13px; }
        .arqueo .arq-actions { display: flex; gap: 8px; }
        .arqueo .arq-filters { background: #f7f7f9; border-radius: 8px; padding: 12px 6px 4px; margin: 0 0 8px; }
        .arqueo .arq-filters label { font-size: 12px; color: #6b7080; margin-bottom: 2px; }
        .arqueo .arq-filters .form-control { margin-bottom: 8px; }
        .arqueo .arq-loading { font-size: 12px; color: #888ea8; }
        .arqueo .arq-section { margin-top: 24px; }
        .arqueo .arq-section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #3b3f5c; margin-bottom: 12px; }
        .arqueo .arq-section-title span { font-weight: 400; text-transform: none; letter-spacing: 0; color: #888ea8; margin-left: 6px; }
    
        .arqueo .arq-card { background: #fff; border: 1px solid #e8e8ef; border-radius: 8px; padding: 16px 18px; height: 100%; display: flex; flex-direction: column; }
        .arqueo .arq-card[class*="arq-accent-"] { border-left-width: 4px; }
        .arqueo .arq-accent-sales { border-left-color: #620408; }
        .arqueo .arq-accent-purchases { border-left-color: #023b58; }
        .arqueo .arq-accent-expenses { border-left-color: #e2a03f; }
        .arqueo .arq-accent-pos { border-left-color: #1abc9c; }
        .arqueo .arq-accent-balance { border-left-color: #888ea8; background: #fafafc; }
        .arqueo .arq-label { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #888ea8; }
        .arqueo .arq-value { font-size: 24px; font-weight: 700; color: #1b2e4b; margin: 4px 0; white-space: nowrap; }
        .arqueo .arq-value small { font-size: 13px; font-weight: 400; color: #888ea8; }
        .arqueo .arq-hint { font-size: 12px; color: #888ea8; }
        .arqueo .arq-pos { color: #1a9a6c !important; }
        .arqueo .arq-neg { color: #c0392b !important; }
    
        .arqueo .arq-statement { padding: 8px 18px 16px; }
        .arqueo .arq-line { display: flex; justify-content: space-between; align-items: baseline; padding: 10px 0; border-bottom: 1px solid #f0f0f4; font-size: 14px; color: #3b3f5c; }
        .arqueo .arq-line strong { color: #1b2e4b; white-space: nowrap; }
        .arqueo .arq-line em { font-style: normal; font-size: 12px; color: #888ea8; margin-left: 6px; }
        .arqueo .arq-subtotal { font-weight: 600; }
        .arqueo .arq-total { border-bottom: 0; border-top: 2px solid #1b2e4b; margin-top: 4px; font-size: 16px; font-weight: 700; }
        .arqueo .arq-total strong { font-size: 20px; }
        .arqueo .arq-margin { height: 6px; background: #f0f0f4; border-radius: 3px; margin-top: 12px; overflow: hidden; }
        .arqueo .arq-margin-bar { height: 100%; background: #1a9a6c; }
        .arqueo .arq-note { margin-top: 12px; font-size: 12px; color: #8a6d3b; background: #fcf8e3; border-radius: 6px; padding: 8px 10px; }
    
        .arqueo .arq-card-table { padding: 0; overflow: hidden; }
        .arqueo .arq-card-head { display: flex; align-items: center; gap: 8px; padding: 12px 16px; font-weight: 600; color: #3b3f5c; border-bottom: 1px solid #e8e8ef; }
        .arqueo .arq-card-head span { background: #f0f0f4; color: #6b7080; border-radius: 10px; padding: 0 8px; font-size: 12px; font-weight: 600; }
        .arqueo .arq-head-btn { margin-left: auto; padding: 2px 10px; font-size: 12px; }
        .arqueo .arq-link { margin-top: auto; padding-top: 10px; font-size: 13px; font-weight: 600; color: #620408; }
    .arqueo .arq-link:hover { text-decoration: underline; }
    .arqueo .arq-head-link { margin: 0 0 0 auto; padding-top: 0; }
    .arqueo .arq-link-add { color: #b7791f; }
    .arqueo .arq-card-actions { display: flex; justify-content: space-between; margin-top: auto; }
    .arqueo .arq-utility { padding: 8px 18px; }
    .arqueo .arq-utility .arq-statement { padding-top: 4px; padding-bottom: 8px; }
    .arqueo .arq-net { display: flex; flex-direction: column; height: 100%; padding: 14px 4px 10px 24px; border-left: 1px solid #f0f0f4; }
    .arqueo .arq-net-value { font-size: 32px; font-weight: 700; margin: 4px 0; white-space: nowrap; }
    .arqueo .arq-net-value small { font-size: 14px; font-weight: 400; color: #888ea8; }
    .arqueo .arq-net .arq-margin { margin-top: 8px; }
    @media (max-width: 991px) { .arqueo .arq-net { border-left: 0; border-top: 1px solid #f0f0f4; padding-left: 4px; } }
    .arqueo .arq-scroll { max-height: 320px; overflow-y: auto; }
    .arqueo .arq-scroll-lg { max-height: 520px; overflow-y: auto; }
        .arqueo .arq-table thead th { background: #f7f7f9 !important; color: #6b7080 !important; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; border: 0; padding: 8px 16px; position: sticky; top: 0; }
        .arqueo .arq-table td { border-top: 1px solid #f0f0f4; padding: 8px 16px; font-size: 13px; color: #3b3f5c; }
        .arqueo .arq-table tfoot td { font-weight: 700; border-top: 2px solid #e8e8ef; }
        .arqueo .arq-empty { text-align: center; color: #b0b4c3 !important; padding: 20px !important; }
    </style>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function(){
        // Modal de detalles
        window.livewire.on('show-modal', msg => {
            $('#modal-details').modal('show');
        });

        // Modal de gastos extras
        window.livewire.on('show-expense-modal', () => {
            $('#expense-modal').modal('show');
        });

        window.livewire.on('close-modal', () => {
            $('#expense-modal').modal('hide');
        });

        // Descarga de PDF
        window.livewire.on('downloadPDF', () => {
            // Hacer una petición GET a la ruta del PDF
            fetch('{{ route("cashout.export-pdf") }}')
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'Corte_Caja.pdf';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                });
        });
    });
</script>
