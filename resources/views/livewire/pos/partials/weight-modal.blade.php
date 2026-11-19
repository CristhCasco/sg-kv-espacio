@if($showWeightModal)
<div class="modal fade show" id="weightModal" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #620408;">
                <h5 class="modal-title text-white">
                    <i class="fas fa-weight"></i> Ingresar Peso Manual
                </h5>
                <button type="button" class="close text-white" wire:click="closeWeightModal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                @php
                    $weightInputNumber = is_numeric(str_replace(',', '.', (string) $weightModalWeight))
                        ? floatval(str_replace(',', '.', (string) $weightModalWeight))
                        : 0;
                    $pesoEnKg = $weightModalUnit === 'g'
                        ? ($weightInputNumber / 1000)
                        : $weightInputNumber;
                    $stockEnUnidad = $weightModalUnit === 'g'
                        ? round(floatval($weightModalStock) * 1000)
                        : floatval($weightModalStock);
                    $minInput = $weightModalUnit === 'kg' ? 0.001 : 1;
                    $canConfirm = $pesoEnKg > 0
                        && $pesoEnKg <= floatval($weightModalStock)
                        && ($weightModalUnit !== 'g' || floor($weightInputNumber) == $weightInputNumber);
                @endphp

                <div class="text-center mb-3">
                    <h5>{{ $weightModalProductName }}</h5>
                    <p class="text-muted mb-0">Precio por Kg: <strong>{{ number_format($weightModalPrice, 0) }} Gs.</strong></p>
                    <small class="text-muted d-block">Stock disponible: <strong>{{ Num::qty($stockEnUnidad) }} {{ $weightModalUnit === 'kg' ? 'Kg' : 'g' }}</strong></small>
                </div>

                <div class="form-group text-center mb-3">
                    <label class="d-block mb-2"><strong>Unidad de medida</strong></label>
                    <div class="btn-group" role="group">
                        <button type="button" 
                                class="btn {{ $weightModalUnit === 'kg' ? 'btn-dark' : 'btn-outline-dark' }}"
                                wire:click="setWeightModalUnit('kg')">
                            Kilogramos (Kg)
                        </button>
                        <button type="button" 
                                class="btn {{ $weightModalUnit === 'g' ? 'btn-dark' : 'btn-outline-dark' }}"
                                wire:click="setWeightModalUnit('g')">
                            Gramos (g)
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="manualWeight">
                        <strong>Peso ({{ $weightModalUnit === 'kg' ? 'Kg' : 'Gramos' }})</strong>
                    </label>
                    <input type="number" 
                           id="manualWeight"
                           class="form-control form-control-lg text-center" 
                              wire:model="weightModalWeight"
                           wire:keydown.enter="confirmWeightProduct"
                           step="{{ $weightModalUnit === 'kg' ? '0.001' : '1' }}" 
                           min="{{ $minInput }}"
                           max="{{ $stockEnUnidad }}"
                           placeholder="{{ $weightModalUnit === 'kg' ? 'Ej: 1.500' : 'Ej: 500' }}"
                           onkeydown="if(['e','E','+','-'].includes(event.key)) event.preventDefault()"
                           autofocus>
                </div>

                @if($pesoEnKg > 0)
                <div class="alert alert-info text-center mb-0">
                    @if($weightModalUnit === 'g')
                        <small class="d-block text-muted">{{ Num::qty($pesoEnKg) }} Kg</small>
                    @endif
                    <strong>Subtotal: {{ number_format(round($pesoEnKg * $weightModalPrice, 2), 0) }} Gs.</strong>
                </div>
                @endif

                @if($pesoEnKg > floatval($weightModalStock) && floatval($weightModalStock) > 0)
                <div class="alert alert-warning text-center mt-2 mb-0">
                    El peso ingresado supera el stock disponible.
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeWeightModal">CANCELAR</button>
                <button id="btnConfirmWeight"
                        type="button" class="btn btn-dark"
                        wire:click="confirmWeightProduct"
                        {{ $canConfirm ? '' : 'disabled' }}>
                    <i class="fas fa-check"></i> CONFIRMAR
                </button>
            </div>
        </div>
    </div>
</div>
@endif
