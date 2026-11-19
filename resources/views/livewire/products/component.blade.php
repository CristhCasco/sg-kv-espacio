<div class="row sales layout-top-spacing">
	
	<div class="col-sm-12">
		<div class="widget widget-chart-one">
			<div class="widget-heading">
				<h4 class="card-title">
					<b>{{$componentName}} | {{$pageTitle}}</b>
				</h4>
				<ul class="tabs tab-pills">
					@can('Product_Create')
					<li>
						<a href="javascript:void(0)" class="tabmenu bg-dark" data-toggle="modal" data-target="#theModal">Agregar</a>
					</li>
					@endcan
				</ul>
			</div>
			@can('Product_Search')
			@include('common.searchbox')
			@endcan
			<div class="px-3 pb-2">
				<div class="row">
					<div class="col-md-4 col-sm-12 mt-2 mt-md-0">
						<label class="mb-1"><strong>Filtrar por Categoría</strong></label>
						<select wire:model="categoryFilter" class="form-control">
							<option value="">Todas</option>
							@foreach($categories as $category)
								<option value="{{ $category->id }}">{{ $category->name }}</option>
							@endforeach
						</select>
					</div>
					
					<div class="col-md-4 col-sm-12">
						<label class="mb-1"><strong>Filtrar por IVA</strong></label>
						<select wire:model="vatFilter" class="form-control">
							<option value="">Todos</option>
							@foreach($vatTypes as $vatType)
								<option value="{{ $vatType->rate }}">{{ $vatType->name }}</option>
							@endforeach
						</select>
					</div>

					<div class="col-md-4 col-sm-12">
						<label class="mb-1"><strong>Filtrar por Costo</strong></label>
						<select wire:model="costFilter" class="form-control">
							<option value="">Todos</option>
							<option value="sin_costo">Sin costo cargado</option>
						</select>
					</div>
				</div>
			</div>
			<div class="widget-content">
				
				<div class="table-responsive">
					<table class="table table-bordered table striped mt-1">
						<thead class="text-white" style="background: #620408">
							<tr>
								<th class="table-th text-white">NOMBRE</th>
								<th class="table-th text-white text-center">CODIGO</th>
								<!-- <th class="table-th text-white text-center">DESCRIPCION</th> -->
								<th class="table-th text-white text-center">CATEGORIA</th>
								<th class="table-th text-white text-center">TIPO IVA</th>
								<th class="table-th text-white text-center">MARCA</th>
								<!-- <th class="table-th text-white text-center">MODELO</th> -->
								<th class="table-th text-white text-center">TAMAÑO</th>
								<!-- <th class="table-th text-white text-center">COLOR</th> -->
								<th class="table-th text-white text-center">COSTO DE COMPRA</th>
								<th class="table-th text-white text-center">PRECIO DE VENTA</th>
								<th class="table-th text-white text-center">STOCK</th>
								<th class="table-th text-white text-center">STOCK MINIMO</th>
								<!-- <th class="table-th text-white text-center">IMAGEN</th> -->
								<th class="table-th text-white text-center">ACTIONS</th>
							</tr>
						</thead>
						<tbody>

                            @foreach($data as $product)
							<tr>
								<td><h6 style="font-size: 14px;">{{$product->name}}</h6></td>
                                <td><h6 class="text-center" style="font-size: 14px;">{{$product->barcode}}</h6></td>
								<!-- <td><h6 class="text-center" style="font-size: 14px;">{{$product->description}}</h6></td> -->
								<td><h6 class="text-center" style="font-size: 14px;">{{$product->category}}</h6></td>
								<td><h6 class="text-center" style="font-size: 14px;">{{$product->vat_type ?? 'NO DEFINIDO'}}</h6></td>
                                <td><h6 class="text-center" style="font-size: 14px;">{{$product->brand}}</h6></td>
                                <!-- <td><h6 class="text-center" style="font-size: 14px;">{{$product->model}}</h6></td> -->
                                <td><h6 class="text-center" style="font-size: 14px;">{{$product->size}}</h6></td>
                                <!-- <td><h6 class="text-center" style="font-size: 14px;">{{$product->color}}</h6></td> -->
                                <td><h6 class="text-center" style="font-size: 14px;">{{number_format($product->cost), 0}}</h6></td>
                                <td>
									<h6 class="text-center" style="font-size: 14px;">
										@if($product->is_weighted)
											{{ number_format($product->price_per_kg, 0) }} Gs.
										@else
											{{ number_format($product->price, 0) }} Gs.
										@endif
									</h6>
								</td>
                                <td>
									<h6 class="text-center" style="font-size: 14px;">
										@if($product->is_weighted)
											{{ Num::qty($product->stock) }}
										@else
											{{ number_format($product->stock, 0) }}
										@endif
									</h6>
								</td>
								
                                <td><h6 class="text-center">
									@if($product->is_weighted)
										{{ Num::qty($product->min_stock) }}
									@else
										{{ number_format($product->min_stock, 0) }}
									@endif
								</h6></td>
								

								<!-- <td class="text-center">
									<span>
										<img src="{{ $product->imagen_url }}"
											alt="imagen de ejemplo" height="70" width="80" class="rounded"
											onclick="showImageModal('{{ $product->imagen_url }}')">
									</span>
								</td> -->

							
								

								<td class="text-center">
									@can('Product_Update')
									<a href="javascript:void(0)"
                                    wire:click.prevent="Edit({{$product->id}})"                                        
                                    class="btn btn-dark mtmobile" title="Edit">
										<i class="fas fa-edit"></i>
									</a>
									@endcan

									@can('Product_Destroy')
									<a href="javascript:void(0)"
                                        onclick="Confirm('{{$product->id}}')"
                                        class="btn btn-dark" title="Delete">
										<i class="fas fa-trash"></i>
									</a>
									@endcan


								</td>
							</tr>
                            @endforeach
						</tbody>
					</table>
					{{$data->links()}}
				</div>

			</div>


		</div>

	</div>

	<!-- Incluir el modal Imagen-->
	@include('livewire.modalimage.modalimage')

	@include('livewire.products.form')

	<!-- INCLUIR MODALIMAGE -->

</div>


<script>
	document.addEventListener('DOMContentLoaded', function(){
		const livewireInstance = window.livewire || window.Livewire;
		let lastFocusedField = {
			selector: '#product-name',
			start: null,
			end: null
		};

		const updateFocusedField = (element) => {
			if (!element) return;

			if (element.id) {
				lastFocusedField.selector = '#' + element.id;
			} else if (element.name) {
				lastFocusedField.selector = '[name="' + element.name + '"]';
			} else {
				lastFocusedField.selector = null;
			}

			if (typeof element.selectionStart === 'number' && typeof element.selectionEnd === 'number') {
				lastFocusedField.start = element.selectionStart;
				lastFocusedField.end = element.selectionEnd;
			} else {
				lastFocusedField.start = null;
				lastFocusedField.end = null;
			}
		};

		const restoreFocusedField = () => {
			if (!$('#theModal').hasClass('show')) return;
			if (!lastFocusedField.selector) return;

			const target = document.querySelector('#theModal ' + lastFocusedField.selector);
			if (!target) return;

			if (document.activeElement !== target) {
				target.focus({ preventScroll: true });
			}

			if (
				typeof target.setSelectionRange === 'function' &&
				typeof lastFocusedField.start === 'number' &&
				typeof lastFocusedField.end === 'number'
			) {
				target.setSelectionRange(lastFocusedField.start, lastFocusedField.end);
			}
		};

		$(document).on('focusin input keyup click', '#theModal [data-fast-input]', function () {
			updateFocusedField(this);
		});

		if (window.Livewire && typeof window.Livewire.hook === 'function') {
			window.Livewire.hook('message.processed', () => {
				restoreFocusedField();
			});
		}

		const notifySuccess = (msg) => {
			if (typeof noty === 'function') {
				noty(msg);
				return;
			}

			swal({
				title: 'OK',
				text: msg,
				type: 'success'
			});
		};

		const focusNameField = () => {
			setTimeout(() => {
				const nameInput = document.getElementById('product-name');
				if (nameInput) {
					nameInput.focus();
					nameInput.select();
				}
			}, 120);
		};

		if (livewireInstance && typeof livewireInstance.on === 'function') {
			livewireInstance.on('product-added', msg => {
				$('#theModal').modal('show');
				focusNameField();
				notifySuccess(msg);
			});

			livewireInstance.on('product-updated', msg => {
				$('#theModal').modal('hide');
			});

			livewireInstance.on('product-deleted', msg => {
				//notificacion
			});

			livewireInstance.on('modal-show', msg => {
				$('#theModal').modal('show');
				focusNameField();
			});

			livewireInstance.on('modal-hide', msg => {
				$('#theModal').modal('hide');
			});
		}

		$('#theModal').on('shown.bs.modal', function () {
			focusNameField();
		});

		$('#theModal').on('hidden.bs.modal', function () {
			const form = document.querySelector('#theModal form');
			if (form) {
				form.reset();
			}

			if (livewireInstance && typeof livewireInstance.emit === 'function') {
				livewireInstance.emit('reset-product-form');
			}
		});

		$(document).on('keydown', '#theModal [data-fast-input]', function (e) {
			if (e.key !== 'Enter') return;

			e.preventDefault();
			const inputs = $('#theModal [data-fast-input]:visible:enabled');
			const index = inputs.index(this);

			if (index > -1 && index < inputs.length - 1) {
				$(inputs[index + 1]).focus();
			}
		});

		$(document).on('keydown', function (e) {
			const isCloseShortcut = e.key === 'Escape';
			if (isCloseShortcut) {
				if (!$('#theModal').hasClass('show')) return;
				if ($('#theModal #product-close-btn:visible:enabled').length === 0) return;

				e.preventDefault();
				const closeButton = document.getElementById('product-close-btn');
				if (closeButton) {
					closeButton.click();
				}
				return;
			}

			const isFastSave = e.key === 'F9' || (e.key === 'Enter' && e.ctrlKey);
			if (!isFastSave) return;
			if (!$('#theModal').hasClass('show')) return;
			if ($('#theModal #product-save-btn:visible:enabled').length === 0) return;

			e.preventDefault();
			const saveButton = document.getElementById('product-save-btn');
			if (saveButton) {
				saveButton.click();
			}
		});

		
    });

	function Confirm(id, products)
    {
        if(products > 0)
        {
            swal('NO SE PUEDE ELIMINAR LA CATEGORIA PORQUE TIENE PRODUCTOS ASOCIADOS');
            return;
        }
        swal({
            title: 'CONFIRMAR',
            text: '¿CONFIRMAS ELIMINAR EL REGISTRO?',
            type: 'warning',
            showCancelButton: true,
            cancelButtonText: 'CERRAR',
            cancelButtonColor: '#fff',
            confirmButtonText: 'ACEPTAR',
            confirmButtonColor: '#620408'
        }).then(function(result){
            if(result.value){
                window.livewire.emit('deleteRow', id);
                swal.close();
            }
        });
    }

	function showImageModal(imageUrl) {
        document.getElementById('productImage').src = imageUrl;
        $('#imageModal').modal('show');
    }
	
</script>