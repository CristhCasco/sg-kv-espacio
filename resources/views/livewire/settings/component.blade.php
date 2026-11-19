<div class="row sales layout-top-spacing">

    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>{{ $componentName }} | {{ $pageTitle }}</b>
                </h4>
                <ul class="tabs tab-pills">
                    <li>
                        <a href="javascript:void(0)" wire:click="setTab('empresa')"
                           class="tabmenu {{ $tab === 'empresa' ? 'bg-dark' : 'bg-light text-dark' }}">Empresa</a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" wire:click="setTab('facturacion')"
                           class="tabmenu {{ $tab === 'facturacion' ? 'bg-dark' : 'bg-light text-dark' }}">Facturación</a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" wire:click="setTab('impresion')"
                           class="tabmenu {{ $tab === 'impresion' ? 'bg-dark' : 'bg-light text-dark' }}">Impresión</a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" wire:click="setTab('balanza')"
                           class="tabmenu {{ $tab === 'balanza' ? 'bg-dark' : 'bg-light text-dark' }}">Balanza</a>
                    </li>
                </ul>
            </div>

            <div class="widget-content">
                <h6 class="text-center text-warning" wire:loading>POR FAVOR ESPERE</h6>

                {{-- ================= EMPRESA ================= --}}
                @if($tab === 'empresa')
                <p class="text-muted">Estos datos aparecen en tickets, facturas, comprobantes de crédito y reportes.</p>

                <div class="row">
                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Razón social *</label>
                            <input wire:model.lazy="name" type="text" class="form-control" placeholder="ej: Comercial El Esquinazo S.A.">
                            @error('name') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Nombre comercial</label>
                            <input wire:model.lazy="trade_name" type="text" class="form-control" placeholder="ej: El Esquinazo">
                            @error('trade_name') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="col-sm-8 col-md-4">
                        <div class="form-group">
                            <label>RUC (sin DV) *</label>
                            <input wire:model.lazy="ruc" type="text" class="form-control" placeholder="ej: 80012345">
                            @error('ruc') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-2">
                        <div class="form-group">
                            <label>DV *</label>
                            <input wire:model.lazy="dv" type="text" maxlength="1" class="form-control" placeholder="ej: 7">
                            @error('dv') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Actividad económica</label>
                            <input wire:model.lazy="economic_activity" type="text" class="form-control" placeholder="ej: Comercio al por menor de artículos varios">
                            @error('economic_activity') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Dirección *</label>
                            <input wire:model.lazy="address" type="text" class="form-control" placeholder="ej: Av. San Blas c/ Monseñor Rodríguez">
                            @error('address') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Ciudad</label>
                            <input wire:model.lazy="city" type="text" class="form-control" placeholder="ej: Ciudad del Este">
                            @error('city') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Teléfono *</label>
                            <input wire:model.lazy="phone" type="text" class="form-control" placeholder="ej: 0981 123 456">
                            @error('phone') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Correo electrónico</label>
                            <input wire:model.lazy="email" type="email" class="form-control" placeholder="ej: ventas@empresa.com.py">
                            @error('email') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="button" wire:click.prevent="saveCompany()" wire:loading.attr="disabled" class="btn btn-dark">
                        GUARDAR EMPRESA
                    </button>
                </div>
                @endif

                {{-- ================= FACTURACIÓN ================= --}}
                @if($tab === 'facturacion')
                <div class="alert alert-{{ $timbradoStatus['class'] }} mb-3">
                    <b>{{ $timbradoStatus['text'] }}</b>
                    @if($nextInvoice)
                        &nbsp;|&nbsp; Próxima factura: <b>{{ $nextInvoice }}</b>
                    @endif
                    @if($invoice_end !== null && $invoice_current !== null && is_numeric($invoice_end) && is_numeric($invoice_current))
                        &nbsp;|&nbsp; Quedan <b>{{ number_format(max(0, $invoice_end - $invoice_current), 0, ',', '.') }}</b> números en el rango
                    @endif
                </div>

                <div class="row">
                    <div class="col-sm-12 col-md-4">
                        <div class="form-group">
                            <label>N° de timbrado *</label>
                            <input wire:model.lazy="timbrado_number" type="text" class="form-control" placeholder="ej: 18156479">
                            @error('timbrado_number') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="form-group">
                            <label>Inicio de vigencia</label>
                            <input wire:model="timbrado_valid_from" type="date" class="form-control">
                            @error('timbrado_valid_from') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="form-group">
                            <label>Fin de vigencia *</label>
                            <input wire:model="timbrado_valid_until" type="date" class="form-control">
                            @error('timbrado_valid_until') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <div class="form-group">
                            <label>Establecimiento *</label>
                            <input wire:model="establishment_code" type="text" maxlength="3" class="form-control" placeholder="001">
                            @error('establishment_code') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="form-group">
                            <label>Punto de expedición *</label>
                            <input wire:model="expedition_point" type="text" maxlength="3" class="form-control" placeholder="001">
                            @error('expedition_point') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="form-group">
                            <label>N° inicial del rango *</label>
                            <input wire:model="invoice_start" type="number" min="1" class="form-control">
                            @error('invoice_start') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="form-group">
                            <label>N° final del rango *</label>
                            <input wire:model="invoice_end" type="number" min="1" class="form-control">
                            @error('invoice_end') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Última factura emitida (contador) *</label>
                            <input wire:model="invoice_current" type="number" min="0" class="form-control">
                            <small class="text-muted">
                                Modifícalo solo al cargar un timbrado nuevo o al migrar desde otro sistema.
                                @if($lastIssued) Última factura registrada en el sistema: <b>{{ $lastIssued }}</b>. @endif
                            </small>
                            @error('invoice_current') <br><span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="button" wire:click.prevent="saveInvoicing()" wire:loading.attr="disabled" class="btn btn-dark">
                        GUARDAR FACTURACIÓN
                    </button>
                </div>
                @endif

                {{-- ================= IMPRESIÓN ================= --}}
                @if($tab === 'impresion')
                <div class="row">
                    {{-- Logo --}}
                    <div class="col-sm-12 col-md-5">
                        <label><b>Logo de la empresa</b></label>
                        <div class="border rounded p-3 mb-2 text-center" style="min-height: 130px;">
                            @if($logoUpload && in_array(strtolower($logoUpload->extension()), ['png', 'jpg', 'jpeg']))
                                <img src="{{ $logoUpload->temporaryUrl() }}" alt="Vista previa" style="max-width: 100%; max-height: 110px;">
                                <div><small class="text-warning">Vista previa: se aplica al guardar</small></div>
                            @elseif($currentLogo)
                                <img src="{{ $currentLogo }}" alt="Logo actual" style="max-width: 100%; max-height: 110px;">
                            @else
                                <span class="text-muted d-block mt-4">Sin logo cargado</span>
                            @endif
                        </div>
                        <input type="file" wire:model="logoUpload" accept="image/png,image/jpeg" class="form-control">
                        <small class="text-muted">PNG o JPG, máximo 1 MB. Para tickets térmicos se ve mejor en blanco y negro, con fondo blanco o transparente.</small>
                        <div wire:loading wire:target="logoUpload"><small class="text-warning">Subiendo imagen...</small></div>
                        @error('logoUpload') <br><span class="text-danger er">{{ $message }}</span> @enderror
                        @if($currentLogo && !$logoUpload)
                            <div class="mt-2">
                                <button type="button" wire:click="removeLogo" class="btn btn-sm btn-outline-danger">Quitar logo</button>
                            </div>
                        @endif
                    </div>

                    {{-- Opciones de ticket --}}
                    <div class="col-sm-12 col-md-7">
                        <div class="form-group">
                            <label><b>Ancho del papel</b></label>
                            <select wire:model="ticket_width" class="form-control">
                                <option value="80">80 mm (impresora térmica estándar)</option>
                                <option value="58">58 mm (impresora térmica pequeña)</option>
                            </select>
                            @error('ticket_width') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="d-flex align-items-center" style="gap: 8px; cursor: pointer;">
                                <input type="checkbox" wire:model="ticket_show_logo">
                                <span>Imprimir el logo en tickets y facturas</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label><b>Al terminar una venta</b></label>
                            <select wire:model="print_after_sale" class="form-control">
                                <option value="ask">Preguntar qué imprimir (ticket o factura)</option>
                                <option value="ticket">Imprimir el ticket automáticamente</option>
                                <option value="none">No imprimir nada</option>
                            </select>
                            <small class="text-muted">Las ventas con factura legal siempre abren la factura para imprimir.</small>
                            @error('print_after_sale') <br><span class="text-danger er">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label><b>Método de impresión</b></label>
                            <select wire:model="print_method" class="form-control">
                                @foreach(\App\Models\Company::PRINT_METHODS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('print_method') <br><span class="text-danger er">{{ $message }}</span> @enderror
                            @if($print_method === 'browser')
                                <small class="text-muted d-block mt-1">
                                    Impresión web: el navegador abre su cuadro de impresión para elegir la impresora. No necesita instalar nada en la caja.
                                </small>
                            @elseif($print_method === 'ninja')
                                <small class="text-muted d-block mt-1">
                                    Tickets, facturas y comprobantes los imprime el programa PrinterNinja, con lo que calcula el sistema. En cada caja (una sola vez):
                                    <br>1. Instala PrinterNinja en <code>C:\apps\printer</code> y elige la impresora en su configuración.
                                    <br>2. Aplica el archivo <code>permitir_printerninja_*.reg</code> (con la dirección de esta caja) para que Chrome abra PrinterNinja sin preguntar, y reinicia Chrome.
                                    <br><b>Versiones:</b> sistema {{ config('app.version') }} · requiere PrinterNinja {{ config('app.printerninja_min') }} o superior (misma versión {{ implode('.', array_slice(explode('.', config('app.version')), 0, 2)) }}.x).
                                </small>
                            @endif
                        </div>

                        <div class="form-group">
                            <label><b>Texto al pie del ticket</b></label>
                            <textarea wire:model.lazy="ticket_footer" rows="3" maxlength="300" class="form-control"
                                      placeholder="Gracias por su compra&#10;No se aceptan devoluciones sin ticket"></textarea>
                            <small class="text-muted">Cada línea se imprime centrada. Si lo dejas vacío se imprime "Gracias por su compra".</small>
                            @error('ticket_footer') <br><span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="button" wire:click.prevent="savePrinting()" wire:loading.attr="disabled" wire:target="savePrinting,logoUpload" class="btn btn-dark">
                        GUARDAR IMPRESIÓN
                    </button>
                </div>
                @endif

                {{-- ================= BALANZA ================= --}}
                @if($tab === 'balanza')
                <p class="text-muted mb-2">
                    Formato de las etiquetas que imprime la balanza. Al escanear en el POS:
                </p>
                <ol class="text-muted">
                    <li>Si existe un producto con ese código exacto, se usa ese producto (los productos que no pasan por la balanza siguen igual).</li>
                    <li>Si la etiqueta empieza con el <b>prefijo de peso</b>, se lee el peso. El producto debe estar marcado "por peso".</li>
                    <li>Si empieza con el <b>prefijo de unidad</b>, se lee la cantidad. El producto no debe estar marcado "por peso".</li>
                </ol>
                <p class="text-muted">
                    El código del producto en la balanza (PLU) debe ser igual al código de barras del producto en el sistema, sin los ceros de adelante (000002 = 2).
                </p>

                <div class="row">
                    <div class="col-sm-12 col-md-7">
                        <div class="form-group">
                            <label class="d-flex align-items-center" style="gap: 8px; cursor: pointer;">
                                <input type="checkbox" wire:model="scale_enabled">
                                <span><b>Leer etiquetas de balanza en el POS</b></span>
                            </label>
                        </div>

                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Prefijo de peso</label>
                                    <input wire:model.lazy="scale_weight_prefix" type="text" maxlength="5" class="form-control" placeholder="2">
                                    @error('scale_weight_prefix') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Prefijo de unidad</label>
                                    <input wire:model.lazy="scale_unit_prefix" type="text" maxlength="5" class="form-control" placeholder="1">
                                    @error('scale_unit_prefix') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Largo de la etiqueta *</label>
                                    <input wire:model.lazy="scale_barcode_length" type="number" min="8" class="form-control">
                                    @error('scale_barcode_length') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-sm-12 mb-2">
                                <small class="text-muted">Deja un prefijo vacío para no leer ese tipo de etiqueta.</small>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Código del producto: desde el dígito *</label>
                                    <input wire:model.lazy="scale_code_start" type="number" min="1" class="form-control">
                                    @error('scale_code_start') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Cantidad de dígitos *</label>
                                    <input wire:model.lazy="scale_code_length" type="number" min="1" class="form-control">
                                    @error('scale_code_length') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Peso / cantidad: desde el dígito *</label>
                                    <input wire:model.lazy="scale_value_start" type="number" min="1" class="form-control">
                                    @error('scale_value_start') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Cantidad de dígitos *</label>
                                    <input wire:model.lazy="scale_value_length" type="number" min="1" class="form-control">
                                    @error('scale_value_length') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Decimales del peso *</label>
                                    <input wire:model.lazy="scale_weight_decimals" type="number" min="0" class="form-control">
                                    <small class="text-muted">3 = gramos (00550 → 0,550 kg)</small>
                                    @error('scale_weight_decimals') <br><span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Decimales de la cantidad *</label>
                                    <input wire:model.lazy="scale_unit_decimals" type="number" min="0" class="form-control">
                                    <small class="text-muted">3 = 03000 → 3 unidades</small>
                                    @error('scale_unit_decimals') <br><span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Probador --}}
                    <div class="col-sm-12 col-md-5">
                        <div class="border rounded p-3">
                            <label><b>Probar una etiqueta</b></label>
                            <input wire:model.debounce.400ms="scaleTestBarcode" type="text" class="form-control" placeholder="Escanea o escribe el código">
                            <small class="text-muted">Usa los valores de la pantalla aunque todavía no estén guardados.</small>

                            @if($scaleTest)
                                <div class="mt-3">
                                    @if($scaleTest['ok'] && $scaleTest['exact'])
                                        <div class="alert alert-info mb-0">
                                            Código exacto de un producto: <b>{{ $scaleTest['product']->name }}</b><br>
                                            Se agrega como producto normal (no se lee como etiqueta de balanza).
                                        </div>
                                    @elseif($scaleTest['ok'])
                                        @php $l = $scaleTest['label']; $p = $l['product']; @endphp
                                        <div class="alert alert-success mb-0">
                                            Etiqueta de <b>{{ $l['type'] === 'weight' ? 'PESO' : 'UNIDAD' }}</b><br>
                                            Producto: <b>{{ $p->name }}</b> (código {{ $l['code'] }})<br>
                                            @if($l['type'] === 'weight')
                                                Peso: <b>{{ Num::qty($l['value']) }} kg</b><br>
                                                Precio por kg: Gs. {{ number_format($p->price_per_kg, 0, ',', '.') }}<br>
                                                Total: <b>Gs. {{ number_format(round($l['value'] * $p->price_per_kg), 0, ',', '.') }}</b>
                                            @else
                                                Cantidad: <b>{{ (int) $l['value'] }}</b><br>
                                                Precio unitario: Gs. {{ number_format($p->price, 0, ',', '.') }}<br>
                                                Total: <b>Gs. {{ number_format((int) $l['value'] * $p->price, 0, ',', '.') }}</b>
                                            @endif
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0">{{ $scaleTest['message'] }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="text-right mt-3">
                    <button type="button" wire:click="resetScaleDefaults" class="btn btn-outline-secondary">
                        Valores por defecto
                    </button>
                    <button type="button" wire:click.prevent="saveScale()" wire:loading.attr="disabled" class="btn btn-dark">
                        GUARDAR BALANZA
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
