@include('common.modalHead')

<div class="row">

 {{-- CI / RUC: al salir del campo se consulta la base local de la DNIT --}}
    <div class="col-sm-12 col-md-5">
        <div class="form-group">
            <label>CI / RUC *</label>
            <input type="text"
                wire:model.lazy="document_number"
                class="form-control"
                inputmode="numeric"
                placeholder="Ej: 4567890 o 80012345">
            <div wire:loading wire:target="document_number" class="text-muted" style="font-size:.8rem;">
                Consultando DNIT...
            </div>
            @error('document_number') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- DV: viene de la DNIT; se puede cargar a mano y se verifica con el cálculo oficial --}}
    <div class="col-sm-4 col-md-2">
        <div class="form-group">
            <label>DV</label>
            <input type="text"
                class="form-control"
                wire:model.lazy="dv"
                maxlength="1"
                @if($autoDetected) readonly @endif
                placeholder="-">
            @error('dv') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Tipo de persona (se detecta con la DNIT, editable si se carga a mano) --}}
    <div class="col-sm-8 col-md-5">
        <div class="form-group">
            <label>PERSONA *</label>
            <select wire:model="person" class="form-control"
                @if($autoDetected) disabled @endif>
                <option value="Elegir">Elegir</option>
                <option value="FISICA">FISICA</option>
                <option value="JURIDICA">JURIDICA</option>
            </select>
            @if($autoDetected)
                <a href="javascript:void(0)" wire:click="unlockPerson" style="font-size:.8rem;">Corregir datos a mano</a>
            @endif
            @error('person') <span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    @if($dnitMessage)
    <div class="col-sm-12">
        <div class="alert {{ $dnitStatus === 'ACTIVO' ? 'alert-success' : ($dnitStatus ? 'alert-warning' : 'alert-info') }} py-2 mb-3" style="font-size:.85rem;">
            {{ $dnitMessage }}
        </div>
    </div>
    @endif

    {{-- Razón social (solo JURIDICA) --}}
    @if($person === 'JURIDICA')
    <div class="col-sm-12">
        <div class="form-group">
            <label>RAZÓN SOCIAL *</label>
            <input type="text"
                class="form-control"
                wire:model.lazy="company"
                placeholder="Ej: DISTRIBUIDORA S.A."
                @if($autoDetected) readonly @endif>
            @error('company') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>
    @endif

    {{-- Nombre y apellido (solo FISICA) --}}
    @if($person === 'FISICA')
    <div class="col-sm-12 col-md-6">
        <div class="form-group">
            <label>NOMBRE *</label>
            <input wire:model.lazy="name" type="text" class="form-control"
                placeholder="Ej: María José"
                @if($autoDetected) readonly @endif>
            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-sm-12 col-md-6">
        <div class="form-group">
            <label>APELLIDO *</label>
            <input wire:model.lazy="last_name" type="text" class="form-control"
                placeholder="Ej: Acuña Fleitas"
                @if($autoDetected) readonly @endif>
            @error('last_name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>
    @endif

    {{-- Dirección --}}
    <div class="col-sm-12 col-md-12">
        <div class="form-group">
            <label>DIRECCIÓN</label>
            <input type="text"
                class="form-control"
                wire:model.lazy="address"
                placeholder="Ej: Avda. Mcal. López 123">
            @error('address') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Teléfono (celular o fijo) --}}
    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>TELÉFONO</label>
            <input wire:model.lazy="phone" type="text" class="form-control"
                placeholder="Ej: 0983123456 o 021 123456">
            @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Nacimiento (solo FISICA) --}}
    @if($person === 'FISICA')
    <div class="col-sm-12 col-md-4">
        <div class="form-group">
            <label>NACIMIENTO</label>
            <input wire:model.lazy="birthday" type="date" class="form-control">
            @error('birthday') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>
    @endif

    {{-- Correo --}}
    <div class="col-sm-12 col-md-6">
        <div class="form-group">
            <label>CORREO</label>
            <input wire:model.lazy="email" type="email" class="form-control"
                placeholder="Ej: ventas@proveedor.com.py">
            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Imagen --}}
    <div class="col-sm-12 col-md-12">
        <div class="form-group custom-file">
            <input type="file"
                class="custom-file-input"
                wire:model="image"
                accept="image/*">
            <label class="custom-file-label">Imagen</label>
            @error('image') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

</div>

@include('common.modalFooter')
