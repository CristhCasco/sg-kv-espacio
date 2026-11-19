<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            margin: 20px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .meta {
            font-size: 11px;
            margin-bottom: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #cfcfcf;
            padding: 6px;
        }
        th {
            background: #f5f5f5;
            text-align: left;
        }
        .num {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="title">Reporte de Inventario de Productos</div>
    <div class="meta">
        Filtro stock: {{ $filterLabel }}<br>
        Tipo de producto: {{ $productTypeLabel ?? 'Todos' }}<br>
        Categoria: {{ $categoryLabel ?? 'Todas' }}<br>
        Nombre: {{ $productNameLabel ?? 'Todos' }}<br>
        Fecha de generacion: {{ $generatedAt->format('d/m/Y H:i') }}
    </div>

    @php
        $totalInvertido = $products->sum(fn($p) => (float)$p->cost * (float)$p->stock);
        $totalCotizado  = $products->sum(fn($p) => (float)($p->is_weighted ? $p->price_per_kg : $p->price) * (float)$p->stock);
        $ganancia       = $totalCotizado - $totalInvertido;
        $porcentaje     = $totalInvertido > 0 ? (($ganancia / $totalInvertido) * 100) : 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Codigo</th>
                <th>Descripcion</th>
                <th class="num">Cantidad</th>
                <th class="num">Stock Minimo</th>
                <th class="num">Precio Compra</th>
                <th class="num">Precio Venta</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $i => $product)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $product->barcode }}</td>
                    <td>{{ $product->name }}</td>
                    <td class="num">
                        {{ number_format((float) $product->stock, 0, ',', '.') }}
                    </td>
                    <td class="num">
                        {{ number_format((float) $product->min_stock, 0, ',', '.') }}
                    </td>
                    <td class="num">₲ {{ number_format((float) $product->cost, 0, ',', '.') }}</td>
                    <td class="num">₲ {{ number_format((float) ($product->is_weighted ? $product->price_per_kg : $product->price), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No hay datos para el filtro seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="num">TOTAL ({{ $products->count() }} productos)</th>
                <th class="num">₲ {{ number_format($totalInvertido, 0, ',', '.') }}</th>
                <th class="num">₲ {{ number_format($totalCotizado, 0, ',', '.') }}</th>
            </tr>
            <tr>
                <th colspan="5" class="num">GANANCIA ESTIMADA</th>
                <th class="num">₲ {{ number_format($ganancia, 0, ',', '.') }}</th>
                <th class="num">{{ number_format($porcentaje, 1, ',', '.') }}%</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
