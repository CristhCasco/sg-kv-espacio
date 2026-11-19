<div wire:ignore.self class="modal fade" id="modalDetails" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      
      <div class="modal-header bg-dark">
        <h5 class="modal-title text-white">
          <b>Detalle de Venta #{{ $saleId ?? '-' }}</b>
        </h5>
        <h6 class="text-center text-warning" wire:loading>POR FAVOR ESPERE...</h6>
      </div>

      <div class="modal-body">

        <div class="table-responsive">
          <table class="table table-bordered table-striped mt-1">
            <thead class="text-white" style="background: #620408">
              <tr>
                <th class="text-center">FOLIO</th>
                <th class="text-center">PRODUCTO</th>
                <th class="text-center">PRECIO</th>
                <th class="text-center">CANT</th>
                <th class="text-center">IMPORTE</th>
              </tr>
            </thead>

            <tbody>
              @forelse(($details ?? []) as $d)
                @php
                  $precioBase = floatval($d->price);
                  $descuento = floatval($d->manual_discount ?? 0);
                  $precioFinal = max($precioBase - $descuento, 0);
                @endphp

                <tr>
                  <td class="text-center"><h6>{{ $d->id }}</h6></td>
                  <td class="text-center"><h6>{{ $d->product }}</h6></td>

                  <!-- PRECIO -->
                  <td class="text-center">
                    @if($descuento > 0)
                      <div>
                        <small class="text-muted" style="text-decoration: line-through;">
                          {{ number_format($precioBase, 0, ',', '.') }} Gs.
                        </small><br>
                        <strong class="text-success">
                          {{ number_format($precioFinal, 0, ',', '.') }} Gs.
                        </strong><br>
                        <small class="text-success">-{{ number_format($descuento, 0, ',', '.') }} Gs.</small>
                      </div>
                    @else
                      <h6>{{ number_format($precioBase, 0, ',', '.') }} Gs.</h6>
                    @endif
                  </td>

                  <!-- CANTIDAD -->
                  <td class="text-center">
                    <h6>
                      @if($d->is_weighted)
                        {{ number_format($d->quantity, 3, ',', '.') }} kg
                      @else
                        {{ number_format($d->quantity, 0, ',', '.') }}
                      @endif
                    </h6>
                  </td>

                  <!-- IMPORTE -->
                  <td class="text-center">
                    <h6>{{ number_format($precioFinal * $d->quantity, 0, ',', '.') }} Gs.</h6>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted">
                    No hay detalles disponibles para esta venta.
                  </td>
                </tr>
              @endforelse
            </tbody>

            @if(!empty($details) && count($details) > 0)
              <tfoot>
                <tr>
                  <td colspan="4" class="text-right font-weight-bold">TOTAL</td>
                  <td class="text-center">
                    <h5>{{ number_format($sumDetails ?? 0, 0, ',', '.') }} Gs.</h5>
                  </td>
                </tr>
                <tr>
                  <td colspan="4" class="text-right">
                    <h6 class="text-success font-weight-bold">Total Descuento</h6>
                  </td>
                  <td class="text-center">
                    <h6 class="text-success font-weight-bold">
                      {{ number_format($ahorroTotal ?? 0, 0, ',', '.') }} Gs.
                    </h6>
                  </td>
                </tr>
              </tfoot>
            @endif

          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-dark close-btn text-info" data-dismiss="modal">
          CERRAR
        </button>
      </div>
    </div>
  </div>
</div>
