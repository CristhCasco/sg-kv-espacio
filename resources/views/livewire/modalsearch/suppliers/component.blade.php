<div>
    <div wire:ignore.self class="modal fade modal-fullscreen" id="modalSearchSupplier" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">

                    @if(!$creating)
                    <div class="input-group" wire:key="header-busqueda">
                        <input type="text" wire:model="search" id="modal-search-supplier-input"
                            placeholder="Escribe el nombre, apellido, ci, empresa o ruc..." class="form-control">
                        <div class="input-group-prepend">
                            <span class="input-group-text input-gp">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                    <button type="button" wire:key="btn-nuevo-proveedor" wire:click="newSupplier" class="btn btn-dark ml-2 text-nowrap">
                        <i class="fas fa-user-plus mr-1"></i> NUEVO PROVEEDOR
                    </button>
                    @else
                    <h5 class="modal-title mb-0" wire:key="header-alta"><b><i class="fas fa-user-plus mr-1"></i> REGISTRAR NUEVO PROVEEDOR</b></h5>
                    @endif

                </div>
                <div class="modal-body">

                    @if($creating)
                    {{-- Alta rápida: se guarda y se asigna a la compra --}}
                    <div class="row p-2" wire:key="form-alta-proveedor">
                        <div class="col-sm-12 col-md-5">
                            <div class="form-group">
                                <label>CI / RUC *</label>
                                <input type="text" wire:model.lazy="document_number" id="modal-new-document"
                                    class="form-control" inputmode="numeric" placeholder="Ej: 4567890 o 80012345">
                                <div wire:loading wire:target="document_number" class="text-muted" style="font-size:.8rem;">
                                    Consultando DNIT...
                                </div>
                                @error('document_number') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-sm-4 col-md-2">
                            <div class="form-group">
                                <label>DV</label>
                                <input type="text" wire:model.lazy="dv" class="form-control" maxlength="1"
                                    @if($autoDetected) readonly @endif placeholder="-">
                                @error('dv') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-sm-8 col-md-5">
                            <div class="form-group">
                                <label>PERSONA *</label>
                                <select wire:model="person" class="form-control" @if($autoDetected) disabled @endif>
                                    <option value="Elegir">Elegir</option>
                                    <option value="FISICA">FISICA</option>
                                    <option value="JURIDICA">JURIDICA</option>
                                </select>
                                @if($autoDetected)
                                    <a href="javascript:void(0)" wire:click="unlockPerson" style="font-size:.8rem;">Corregir datos a mano</a>
                                @endif
                                @error('person') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        @if($dnitMessage)
                        <div class="col-sm-12">
                            <div class="alert {{ $dnitStatus === 'ACTIVO' ? 'alert-success' : ($dnitStatus ? 'alert-warning' : 'alert-info') }} py-2 mb-3" style="font-size:.85rem;">
                                {{ $dnitMessage }}
                            </div>
                        </div>
                        @endif

                        @if($person === 'JURIDICA')
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>RAZÓN SOCIAL *</label>
                                <input type="text" wire:model.lazy="company" class="form-control"
                                    placeholder="Ej: DISTRIBUIDORA S.A." @if($autoDetected) readonly @endif>
                                @error('company') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @endif

                        @if($person === 'FISICA')
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>NOMBRE *</label>
                                <input type="text" wire:model.lazy="name" class="form-control"
                                    placeholder="Ej: María José" @if($autoDetected) readonly @endif>
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>APELLIDO *</label>
                                <input type="text" wire:model.lazy="last_name" class="form-control"
                                    placeholder="Ej: Acuña Fleitas" @if($autoDetected) readonly @endif>
                                @error('last_name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @endif

                        <div class="col-sm-12 col-md-4">
                            <div class="form-group">
                                <label>TELÉFONO</label>
                                <input type="text" wire:model.lazy="phone" class="form-control" placeholder="Ej: 0983123456 o 021 123456">
                                @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-8">
                            <div class="form-group">
                                <label>DIRECCIÓN</label>
                                <input type="text" wire:model.lazy="address" class="form-control" placeholder="Ej: Avda. Mcal. López 123">
                                @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="row p-2" wire:key="tabla-busqueda">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mt-1">
                                <thead class="text-white" style="background: #620408">
                                    <tr>
                                        <th width="5%" class="table-th text-left text-white">CODIGO</th>
                                        <th class="table-th text-center text-white">NOMBRE</th>
                                        <th width="13%" class="table-th text-center text-white">APELLIDO</th>
                                        <th class="table-th text-center text-white">CEDULA</th>
                                        <th class="table-th text-center text-white">EMPRESA</th>
                                        <th class="table-th text-center text-white">RUC</th>
                                        <th class="table-th text-center text-white">DIRECCION</th>
                                        <th class="table-th text-center text-white">TELÉFONO</th>
                                        <th class="table-th text-center text-white"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($suppliers as $supplier)
                                    <tr>
                                        <td><div class="text-left"><h6><b>{{$supplier->id}}</b></h6></div></td>
                                        <td><div class="text-left"><h6><b>{{$supplier->name}}</b></h6></div></td>
                                        <td><div class="text-left"><h6><b>{{$supplier->last_name}}</b></h6></div></td>
                                        <td><div class="text-left"><h6><b>{{$supplier->ci}}</b></h6></div></td>
                                        <td><div class="text-left"><h6><b>{{$supplier->company}}</b></h6></div></td>
                                        <td><div class="text-left"><h6><b>{{ $supplier->dv ? $supplier->full_document : '' }}</b></h6></div></td>
                                        <td><div class="text-left"><h6><b>{{$supplier->address}}</b></h6></div></td>
                                        <td><div class="text-left"><h6><b>{{$supplier->phone}}</b></h6></div></td>
                                        <td class="text-center">
                                            <button wire:click.prevent="setSupplier({{$supplier->id}})" class="btn btn-dark">
                                                <i class="fas fa-cart-arrow-down mr-1"></i>
                                                AGREGAR PROVEEDOR
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center p-4">
                                            {{-- Cada mensaje en su propio elemento con wire:key: si no, Livewire
                                                 reutiliza los nodos de texto y pierde parte del mensaje --}}
                                            @if(strlen($search) > 0)
                                                <span wire:key="vacio-sin-resultados">No hay proveedores que coincidan con "<b>{{ $search }}</b>". Toca <b>NUEVO PROVEEDOR</b> para registrarlo.</span>
                                            @else
                                                <span wire:key="vacio-inicial">Escribe para buscar un proveedor, o toca <b>NUEVO PROVEEDOR</b> para registrarlo.</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                </div>
                <div class="modal-footer">
                    @if($creating)
                        {{-- wire:key evita que Livewire recicle el botón CERRAR y pierda el wire:click --}}
                        <button type="button" wire:key="btn-volver-busqueda" wire:click="cancelNewSupplier" class="btn btn-outline-dark">VOLVER A LA BÚSQUEDA</button>
                        <button type="button" wire:key="btn-guardar-proveedor" wire:click="saveSupplier" wire:loading.attr="disabled" class="btn btn-dark">
                            <i class="fas fa-save mr-1"></i> GUARDAR Y ASIGNAR
                        </button>
                    @else
                        <button type="button" wire:key="btn-cerrar-modal" class="btn btn-outline-dark" data-dismiss="modal">CERRAR VENTANA</button>
                    @endif
                </div>
            </div>
        </div>
    </div>




    <script>
        document.addEventListener('DOMContentLoaded', function() {

       window.addEventListener('focusInput', event => {

let input = document.getElementById('modal-search-supplier-input');
if(input) {
   input.focus();
} });


window.addEventListener('closeModal', event => {
   $('#modalSearchSupplier').modal('hide');
});

window.livewire.on('supplier-assigned', Msg => {
   noty(Msg);
});

// Al abrir, el cursor va al buscador: si no, lo que se escribe lo toma el lector de códigos como producto
$('#modalSearchSupplier').on('shown.bs.modal', () => {
   const input = document.getElementById('modal-search-supplier-input');
   if (input) {
      input.focus();
      input.select();
   }
});

// Al cerrar el modal se descarta un alta a medio cargar
$('#modalSearchSupplier').on('hidden.bs.modal', () => {
   window.livewire.emitTo('modal-search-suppliers', 'cancelNewSupplier');
});

})


    </script>
</div>
