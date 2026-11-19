@include('common.modalHead')

<form wire:submit.prevent="{{ $selected_id < 1 ? 'Store' : 'Update' }}">
@if($errors->any())
<div class="alert alert-danger" role="alert">
    {{ $errors->first() }}
</div>
@endif
<div class="row">
    <div class="col-12 mb-2">
        <small class="text-muted d-block">Campos obligatorios <span class="text-danger">*</span></small>
        <hr class="mt-1 mb-3">
        <div class="col-12 mb-1">
            <small class="text-muted d-block">Atajo rapido: presiona F9 para guardar y seguir cargando.</small>
            <small class="text-muted d-block">Atajo rapido: presiona Esc para cerrar el formulario.</small>
        </div>
    </div>

    

    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Nombre *</label>
            <input id="product-name" wire:model.defer="name" type="text" class="form-control" placeholder="ej: Remera" autofocus data-fast-input>
        </div>
    </div>
    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Codigo *</label>
            <input wire:model.defer="barcode" type="text" class="form-control" placeholder="ej: 0001" data-fast-input>
        </div>
    </div>

    <div class="col-sm-12 col-md-4">
            <div>
                <label for="is_weighted">¿Se vende por peso? *</label>
                <div class="form-group">
                    <input type="checkbox" id="is_weighted" wire:model="is_weighted" data-fast-input>
                </div>
            </div>
    </div>

    @if(!$is_weighted)
    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label for="price">Precio de Venta por Unidad *</label>
            <input type="number" id="price" wire:model.defer="price" step="0.01" class="form-control" data-fast-input>
            <small class="text-muted d-block">Precio obligatorio: completa por unidad o por kilo, segun corresponda.</small>
        </div>
    </div>
    @endif

    @if($is_weighted)
    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label for="price_per_kg">Precio de Venta por Kilo *</label>
            <input type="number" id="price_per_kg" wire:model.defer="price_per_kg" step="0.01" class="form-control" data-fast-input>
        </div>
    </div>
    @endif

    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Costo de Compra {{ $is_weighted ? 'por Kilo' : 'por Unidad' }} *</label>
            <input wire:model.lazy="cost" type="number" class="form-control" placeholder="ej: 10000" data-fast-input>
            @php
                $salePrice = (float) ($is_weighted ? $price_per_kg : $price);
            @endphp
            @if((float) $cost > 0 && $salePrice > 0 && (float) $cost >= $salePrice)
                <small class="text-warning d-block">Atención: el costo es igual o mayor al precio de venta.</small>
            @endif
        </div>
    </div>

    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Stock *</label>
            <input wire:model.defer="stock" type="number" 
                @if($is_weighted) step="0.001" @else step="1" @endif
                class="form-control" placeholder="ej: 50" data-fast-input>
        </div>
    </div>
     <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Stock Minimo *</label>
            <input wire:model.defer="min_stock" type="number"  
                @if($is_weighted) step="0.001" @else step="1" @endif
                class="form-control" placeholder="ej: 10" data-fast-input>
        </div>
    </div>

    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Categoria *</label>
            <select wire:model.defer='categoryid' class="form-control" data-fast-input>
                <option value="Elegir" disabled>Elegir</option>
                @foreach ($categories as $category)
                <option value="{{$category->id}}">{{$category->name}}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-12 mt-2 mb-2">
        <small class="text-muted d-block">Campos opcionales</small>
        <hr class="mt-1 mb-3">
    </div>

    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Marca</label>
            <input wire:model.lazy="brand" type="text" class="form-control" placeholder="ej: Nike" data-fast-input>
        </div>
    </div>
    
    <!-- <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Modelo</label>
            <input wire:model.lazy="model" type="text" class="form-control" placeholder="ej: Deportivo">
            @error('model') <span class="text-danger er">{{ $message }}</span> @enderror
        </div>
    </div> -->
    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Tamaño</label>
            <input wire:model.lazy="size" type="text" class="form-control" placeholder="ej: 12/14/P/M/G/GG" data-fast-input>
        </div>
    </div>
    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Color</label>
            <input wire:model.lazy="color" type="text" class="form-control" placeholder="ej: AZUL" data-fast-input>
        </div>
    </div>
    <div class="col-sm-12 col-md-8">
        <div class="form-group">
            <label>Descripcion</label>
            <input wire:model.lazy="description" type="text" class="form-control" placeholder="ej: Elegante Remera Deportiva" data-fast-input>
        </div>
    </div>

    <!-- <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Precio *</label>
            @can('MODIFICPRICE')
            <input wire:model.lazy="price" type="number"  class="form-control" placeholder="ej: 15000">
            @error('price') <span class="text-danger er">{{ $message }}</span> @enderror
            @else
            {}
            @endcan
        </div>
    </div> -->

    <!-- <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>Precio *</label>
            <input wire:model.lazy="price" type="number" class="form-control" placeholder="ej: 15000">
            @error('price') <span class="text-danger er">{{ $message }}</span> @enderror
        </div>
    </div> -->

     <!-- <div class="col-sm-12 col-md-8">
        <div class="form-group custom-file">
            <input type="file" class="custom-file-input" wire:model="image"
            accept="image/x-png, image/gif, image/jpeg"
            >
            <label class="custom-file-label"> Imagen {{$image}}</label>
            @error('image') <span class="text-danger er">{{ $message }}</span> @enderror
        </div>
    </div> -->
    

</div>


<div>
    <div class="modal-footer">
        <button id="product-close-btn" type="button" class="btn btn-dark close-btn text-info" data-dismiss="modal">CERRAR
        </button>

        @if($selected_id < 1)
        <button id="product-save-btn" type="submit" class="btn btn-dark">GUARDAR
        </button>
        @else
        <button id="product-update-btn" type="submit" class="btn btn-dark">ACTUALIZAR
        </button>
        @endif
    </div>
</div>
</form>
