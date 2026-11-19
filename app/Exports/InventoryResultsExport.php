<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

// Resultados de "Generar Conteo": las tablas de inventario se vacían, por eso llegan desde la sesión
class InventoryResultsExport implements FromArray, WithHeadings, ShouldAutoSize
{
    private $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function array(): array
    {
        return array_map(function ($r) {
            return [
                $r['barcode'],
                $r['description'],
                $r['quantity_excel'],
                $r['quantity_counted'],
                $r['difference'],
                $r['user'],
                $r['date'],
            ];
        }, $this->results);
    }

    public function headings(): array
    {
        return ['Código', 'Descripción', 'Cantidad en Excel', 'Cantidad Contada', 'Diferencia', 'Usuario', 'Fecha'];
    }
}
