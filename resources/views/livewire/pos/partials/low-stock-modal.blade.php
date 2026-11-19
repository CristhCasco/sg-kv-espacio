<div wire:ignore.self class="modal fade" id="lowStockModal" tabindex="-1" role="dialog" aria-labelledby="lowStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.12);">

            <!-- HEADER -->
            <div class="modal-header" style="background: #620408; border-bottom: none; padding: 13px 20px;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle mr-2" style="font-size: 1rem; color: #f0c0a0;"></i>
                    <h5 class="modal-title mb-0" id="lowStockModalLabel" style="color:#fff; font-weight:600; font-size:.95rem; letter-spacing:.4px;">
                        Stock Mínimo / Bajo
                        @if(count($lowStockProducts) > 0)
                            <span class="badge ml-2" style="background:rgba(255,255,255,0.15); color:#f0c0a0; font-size:.72rem; font-weight:500;">
                                {{ count($lowStockProducts) }} {{ count($lowStockProducts) != 1 ? 'productos' : 'producto' }}
                            </span>
                        @endif
                    </h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#f0c0a0; opacity:.8; text-shadow:none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- BODY -->
            <div class="modal-body p-0" style="max-height: 65vh; overflow-y: auto; background:#fdfcfc;">
                @if(count($lowStockProducts) > 0)
                    @php
                        $sinStock = collect($lowStockProducts)->where('stock', '<=', 0)->count();
                    @endphp
                    @if($sinStock > 0)
                        <div class="d-flex align-items-center px-3 py-2" style="background:#fdf0ef; border-bottom:1px solid #e8d0ce;">
                            <i class="fas fa-ban mr-2" style="color:#9b2220; font-size:.8rem;"></i>
                            <small style="color:#9b2220; font-weight:600;">{{ $sinStock }} {{ $sinStock != 1 ? 'productos' : 'producto' }} sin stock</small>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size: .84rem;">
                            <thead style="background: #620408; position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="padding: 9px 12px; font-weight:600; border:none; color:#fff;">#</th>
                                    <th style="padding: 9px 12px; font-weight:600; border:none; color:#fff;">Producto</th>
                                    <th style="padding: 9px 12px; font-weight:600; border:none; color:#fff;">Código</th>
                                    <th class="text-center" style="padding: 9px 12px; font-weight:600; border:none; color:#fff;">Stock</th>
                                    <th class="text-center" style="padding: 9px 12px; font-weight:600; border:none; color:#fff;">Mínimo</th>
                                    <th class="text-center" style="padding: 9px 12px; font-weight:600; border:none; color:#fff;">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockProducts as $i => $item)
                                    @php
                                        $isOut = (float) $item['stock'] <= 0;
                                        $stock = rtrim(rtrim(number_format((float) $item['stock'], 3, '.', ''), '0'), '.');
                                        $minSt = rtrim(rtrim(number_format((float) $item['min_stock'], 3, '.', ''), '0'), '.');
                                        $rowBg = $isOut ? '#fdf4f4' : ($loop->even ? '#ffffff' : '#faf8f8');
                                    @endphp
                                    <tr style="background: {{ $rowBg }}; border-bottom: 1px solid #ede8e8;">
                                        <td style="padding: 7px 12px; color:#bbb;">{{ $i + 1 }}</td>
                                        <td style="padding: 7px 12px; color:#333; font-weight:500;">{{ $item['name'] }}</td>
                                        <td style="padding: 7px 12px;">
                                            <code style="background:#f0eded; color:#555; padding:2px 6px; border-radius:3px; font-size:.77rem;">{{ $item['barcode'] }}</code>
                                        </td>
                                        <td class="text-center" style="padding: 7px 12px; font-weight:700; color: {{ $isOut ? '#9b2220' : '#7a3a0f' }};">
                                            {{ $stock }}
                                        </td>
                                        <td class="text-center" style="padding: 7px 12px; color:#777;">{{ $minSt }}</td>
                                        <td class="text-center" style="padding: 7px 12px;">
                                            @if($isOut)
                                                <span style="background:#fdf0ef; color:#9b2220; border:1px solid #e8c5c3; padding:2px 8px; border-radius:4px; font-size:.72rem; font-weight:600;">Sin stock</span>
                                            @else
                                                <span style="background:#fdf6ee; color:#7a3a0f; border:1px solid #e8d5c0; padding:2px 8px; border-radius:4px; font-size:.72rem; font-weight:600;">Stock bajo</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle" style="font-size:2rem; color:#8aab8a;"></i>
                        <p class="mt-3 mb-0" style="color:#888; font-size:.88rem;">No hay productos con stock mínimo o bajo.</p>
                    </div>
                @endif
            </div>

            <!-- FOOTER -->
            <div class="modal-footer" style="background:#faf8f8; border-top: 1px solid #ede8e8; padding: 9px 16px;">
                <button type="button" class="btn btn-sm" wire:click="exportLowStockPdf"
                    style="background:#620408; color:#fff; border:none; border-radius:5px; padding:5px 14px; font-size:.81rem;">
                    <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
                </button>
                <button type="button" class="btn btn-sm btn-light" data-dismiss="modal"
                    style="border-radius:6px; font-size:.82rem; padding:6px 14px;">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>
