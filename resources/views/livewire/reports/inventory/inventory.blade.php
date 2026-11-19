
@extends('layouts.theme.app')

@section('content')
<div class="container">
    <h4 class="text-center"><b>Reporte de Inventario de Productos</b></h4>

    <form method="GET" action="{{ route('inventory.reports') }}" class="mb-3" id="inventoryFilterForm">
        <div class="card p-3">
            <div class="form-row align-items-end">
                <div class="form-group col-md-4 mb-2">
                    <label for="product_name"><b>Nombre del producto</b></label>
                    <input type="text" name="product_name" id="product_name" class="form-control"
                        value="{{ $productNameFilter ?? '' }}" placeholder="Ej: AJO"
                        oninput="handleProductNameFilterInput(this)">
                </div>

                <div class="form-group col-md-3 mb-2">
                    <label for="category_id"><b>Categoría</b></label>
                            <select name="category_id" id="category_id" class="form-control">
                        <option value="0" {{ (int)($categoryFilter ?? 0) === 0 ? 'selected' : '' }}>Todas</option>
                        @foreach(($categories ?? collect()) as $category)
                            <option value="{{ $category->id }}" {{ (int)($categoryFilter ?? 0) === (int)$category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-2 mb-2">
                    <label for="product_type"><b>Tipo</b></label>
                            <select name="product_type" id="product_type" class="form-control">
                        <option value="all" {{ ($productTypeFilter ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="peso" {{ ($productTypeFilter ?? 'all') === 'peso' ? 'selected' : '' }}>Por peso</option>
                        <option value="unidad" {{ ($productTypeFilter ?? 'all') === 'unidad' ? 'selected' : '' }}>Por unidad</option>
                    </select>
                </div>

                <div class="form-group col-md-3 mb-2">
                    <label for="stock_filter"><b>Stock</b></label>
                            <select name="stock_filter" id="stock_filter" class="form-control">
                        <option value="all" {{ ($stockFilter ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="minimo" {{ ($stockFilter ?? 'all') === 'minimo' ? 'selected' : '' }}>Stock minimo o menor</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-2">
                <button type="submit" formaction="{{ route('export.products') }}" class="btn btn-success mr-2">
                    Exportar a Excel
                </button>

                <button type="submit" formaction="{{ route('export.products.pdf') }}" formtarget="_blank" class="btn btn-danger">
                    Exportar a PDF A4
                </button>
            </div>
        </div>
    </form>

    @php
        $stockLabel = ($stockFilter ?? 'all') === 'minimo' ? 'Stock minimo o menor' : 'Todos';
        $typeLabel = [
            'all' => 'Todos',
            'peso' => 'Por peso',
            'unidad' => 'Por unidad',
        ][$productTypeFilter ?? 'all'] ?? 'Todos';
        $categoryLabel = optional(($categories ?? collect())->firstWhere('id', (int)($categoryFilter ?? 0)))->name ?? 'Todas';
        $nameLabel = trim((string)($productNameFilter ?? '')) !== '' ? $productNameFilter : 'Todos';
    @endphp

    <div class="alert alert-light border mb-3">
        <strong>Filtros activos:</strong>
        Stock: <strong>{{ $stockLabel }}</strong> |
        Tipo: <strong>{{ $typeLabel }}</strong> |
        Categoria: <strong>{{ $categoryLabel }}</strong> |
        Nombre: <strong>{{ $nameLabel }}</strong>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Stock Mínimo</th>
                <th>Precio de Compra</th>
                <th>Precio de Venta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $i => $product)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $product->barcode }}</td>
                <td>{{ $product->name }}</td>
                <td>
                    {{ number_format((float) $product->stock, 0, ',', '.') }}
                </td>
                <td>
                    {{ number_format((float) $product->min_stock, 0, ',', '.') }}
                </td>
                <td>Gs. {{ number_format((float) $product->cost, 0, ',', '.') }}</td>
                <td>
                    Gs. {{ number_format((float) ($product->is_weighted ? $product->price_per_kg : $product->price), 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
        @php
            $totalInvertido = $products->sum(fn($p) => (float)$p->cost * (float)$p->stock);
            $totalCotizado  = $products->sum(fn($p) => (float)($p->is_weighted ? $p->price_per_kg : $p->price) * (float)$p->stock);
            $ganancia       = $totalCotizado - $totalInvertido;
            $porcentaje     = $totalInvertido > 0 ? (($ganancia / $totalInvertido) * 100) : 0;
        @endphp
        <tfoot>
            <tr class="table-dark">
                <td colspan="5" class="text-right"><strong>TOTAL ({{ $products->count() }} productos)</strong></td>
                <td><strong>Gs. {{ number_format($totalInvertido, 0, ',', '.') }}</strong></td>
                <td><strong>Gs. {{ number_format($totalCotizado, 0, ',', '.') }}</strong></td>
            </tr>
            <tr class="table-secondary">
                <td colspan="5" class="text-right"><strong>GANANCIA ESTIMADA</strong></td>
                <td><strong>Gs. {{ number_format($ganancia, 0, ',', '.') }}</strong></td>
                <td><strong>{{ number_format($porcentaje, 1, ',', '.') }}%</strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
    (function () {
        let productNameFilterTimer = null;
        const inventoryRoute = "{{ route('inventory.reports') }}";

        const submitFilters = function () {
            const form = document.getElementById('inventoryFilterForm');
            if (!form) {
                return;
            }

            form.action = inventoryRoute;
            form.target = '_self';
            form.submit();
        };

        window.handleProductNameFilterInput = function (input) {
            clearTimeout(productNameFilterTimer);

            const value = (input.value || '').trim();
            if (value.length !== 0 && value.length < 2) {
                return;
            }

            productNameFilterTimer = setTimeout(function () {
                submitFilters();
            }, 350);
        };

        ['category_id', 'product_type', 'stock_filter'].forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) {
                return;
            }

            el.addEventListener('change', submitFilters);
        });
    })();
</script>
@endsection