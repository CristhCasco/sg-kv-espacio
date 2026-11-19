@if($showOutOfStockModal)
<div class="modal fade show" id="outOfStockModal" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #620408;">
                <h5 class="modal-title text-white">
                    <i class="fas fa-box-open"></i> Producto Sin Stock
                </h5>
                <button type="button" class="close text-white" wire:click="closeOutOfStockModal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="text-center mb-3">
                    <h5 class="mb-1">{{ $outOfStockProductName }}</h5>
                    <small class="text-muted d-block">Stock actual: <strong>{{ number_format($outOfStockCurrentStock, 0) }}</strong></small>
                    <small class="text-muted d-block">Cantidad solicitada: <strong>{{ $outOfStockRequestedQty }}</strong></small>
                </div>

                <div class="form-group mb-0">
                    <label for="outStockQty"><strong>Cantidad a agregar al stock</strong></label>
                    <input
                        type="number"
                        id="outStockQty"
                        class="form-control form-control-lg text-center"
                        wire:model="outOfStockAddQty"
                        wire:keydown.enter="confirmOutOfStockUpdate"
                        min="1"
                        step="1"
                        onkeydown="if(['e','E','+','-','.'].includes(event.key)) event.preventDefault()"
                        autofocus>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeOutOfStockModal">Cancelar</button>
                <button type="button" class="btn btn-dark" wire:click="confirmOutOfStockUpdate">
                    <i class="fas fa-check"></i> Actualizar Stock
                </button>
            </div>
        </div>
    </div>
</div>
@endif
