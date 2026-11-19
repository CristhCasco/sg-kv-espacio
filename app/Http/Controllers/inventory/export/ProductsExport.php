<?php
namespace App\Http\Controllers\inventory\export;

use App\Models\Product;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class ProductsExport implements FromCollection, WithHeadings, WithStyles, WithCustomStartCell, WithEvents
{
    protected $stockFilter;
    protected $productTypeFilter;
    protected $productNameFilter;
    protected $categoryFilter;
    protected $stockFilterLabel;
    protected $productTypeLabel;
    protected $categoryLabel;
    protected $productNameLabel;
    protected $totalInvertido = 0;
    protected $totalCotizado = 0;
    protected $ganancia = 0;
    protected $porcentaje = 0;
    protected $totalProductos = 0;

    public function __construct($stockFilter = 'all', $productTypeFilter = 'all', $productNameFilter = '', $categoryFilter = 0)
    {
        $this->stockFilter = $stockFilter;
        $this->productTypeFilter = $productTypeFilter;
        $this->productNameFilter = trim((string) $productNameFilter);
        $this->categoryFilter = (int) $categoryFilter;

        $this->stockFilterLabel = [
            'all' => 'Todos',
            'minimo' => 'Stock minimo o menor',
        ][$this->stockFilter] ?? 'Todos';

        $this->productTypeLabel = [
            'all' => 'Todos',
            'peso' => 'Solo por peso',
            'unidad' => 'Solo por unidad',
        ][$this->productTypeFilter] ?? 'Todos';

        $this->categoryLabel = 'Todas';
        if ($this->categoryFilter > 0) {
            $this->categoryLabel = optional(Category::find($this->categoryFilter))->name ?? 'Todas';
        }

        $this->productNameLabel = $this->productNameFilter !== '' ? $this->productNameFilter : 'Todos';
    }

    public function collection()
    {
        $query = Product::select('barcode', 'name', 'stock', 'cost', 'price', 'price_per_kg', 'is_weighted');

        if ($this->stockFilter === 'minimo') {
            $query->whereRaw('COALESCE(stock,0) <= COALESCE(min_stock,0)');
        }

        if ($this->productTypeFilter === 'peso') {
            $query->where('is_weighted', 1);
        } elseif ($this->productTypeFilter === 'unidad') {
            $query->where('is_weighted', 0);
        }

        if ($this->productNameFilter !== '') {
            $query->where('name', 'like', '%' . $this->productNameFilter . '%');
        }

        if ($this->categoryFilter > 0) {
            $query->where('category_id', $this->categoryFilter);
        }

        $products = $query->orderBy('name')->get();

        $this->totalProductos = $products->count();
        $this->totalInvertido = $products->sum(fn($p) => (float)$p->cost * (float)$p->stock);
        $this->totalCotizado = $products->sum(fn($p) => (float)($p->is_weighted ? $p->price_per_kg : $p->price) * (float)$p->stock);
        $this->ganancia = $this->totalCotizado - $this->totalInvertido;
        $this->porcentaje = $this->totalInvertido > 0 ? (($this->ganancia / $this->totalInvertido) * 100) : 0;

        return $products->map(function ($product) {
            return [
                'barcode' => $product->barcode,
                'name' => $product->name,
                // Sin decimales si es entero, pero sin redondear el stock por peso (2.5 kg salía 3)
                'stock' => \App\Helpers\Num::input($product->stock),
                'cost' => number_format((float) $product->cost, 0, '.', ''),
                'price' => number_format((float) ($product->is_weighted ? $product->price_per_kg : $product->price), 0, '.', ''),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'codigo',
            'descripcion',
            'cantidad',
            'costo',
            'precio',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            7 => ['font' => ['bold' => true]],
        ];
    }

    public function startCell(): string
    {
        return 'A7';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->setCellValue('A1', 'Reporte de Inventario de Productos');
                $sheet->setCellValue('A2', 'Filtro stock: ' . $this->stockFilterLabel);
                $sheet->setCellValue('A3', 'Tipo de producto: ' . $this->productTypeLabel);
                $sheet->setCellValue('A4', 'Categoria: ' . $this->categoryLabel);
                $sheet->setCellValue('A5', 'Nombre: ' . $this->productNameLabel);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

                $highestDataRow = $sheet->getHighestDataRow();
                $resumeStart = $highestDataRow + 2;

                $sheet->setCellValue('A' . $resumeStart, 'TOTAL PRODUCTOS');
                $sheet->setCellValue('C' . $resumeStart, $this->totalProductos);
                $sheet->setCellValue('D' . $resumeStart, 'Gs. ' . number_format($this->totalInvertido, 0, ',', '.'));
                $sheet->setCellValue('E' . $resumeStart, 'Gs. ' . number_format($this->totalCotizado, 0, ',', '.'));

                $sheet->setCellValue('A' . ($resumeStart + 1), 'GANANCIA ESTIMADA');
                $sheet->setCellValue('D' . ($resumeStart + 1), 'Gs. ' . number_format($this->ganancia, 0, ',', '.'));
                $sheet->setCellValue('E' . ($resumeStart + 1), number_format($this->porcentaje, 1, ',', '.') . '%');

                $sheet->getStyle('A' . $resumeStart . ':E' . ($resumeStart + 1))->getFont()->setBold(true);
            },
        ];
    }
}