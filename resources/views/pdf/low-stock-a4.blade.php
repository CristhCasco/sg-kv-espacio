<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Stock Bajo</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 20px;
        }
        .header {
            margin-bottom: 16px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .meta {
            font-size: 11px;
            color: #4b5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }
        th {
            background: #f3f4f6;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        .danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
        }
        .footer {
            margin-top: 16px;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Listado de Productos con Stock Minimo o Bajo</div>
        <div class="meta">
            Empresa: {{ optional($company)->name ?? config('app.name') }}<br>
            Fecha de generacion: {{ $generatedAt->format('d/m/Y H:i') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Codigo</th>
                <th class="text-right">Stock</th>
                <th class="text-right">Minimo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $item)
                @php
                    $stock = (float) $item->stock;
                    $min = (float) $item->min_stock;
                    $isOut = $stock <= 0;
                @endphp
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->barcode }}</td>
                    <td class="text-right">{{ Num::qty($stock) }}</td>
                    <td class="text-right">{{ Num::qty($min) }}</td>
                    <td>
                        @if($isOut)
                            <span class="badge danger">Sin stock</span>
                        @else
                            <span class="badge warning">Stock bajo</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No hay productos con stock minimo o bajo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento generado para analisis de reposicion de productos.
    </div>
</body>
</html>
