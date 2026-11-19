<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CashoutExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithProperties, WithEvents
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [];

        // Encabezado
        $rows[] = ['RESUMEN - ' . strtoupper($this->data['type'])];
        $rows[] = ['Período: ' . $this->data['fromDate'] . ' a ' . $this->data['toDate']];
        $rows[] = ['Filtros Aplicados:'];
        $rows[] = ['Usuario: ' . $this->data['userName']];
        if ($this->data['type'] == 'Ventas' || $this->data['type'] == 'Compras') {
            $rows[] = ['Tipo de Pago: ' . $this->data['paymentType']];
            $rows[] = ['Método de Pago: ' . $this->data['paymentMethod']];
        }
        $rows[] = [];

        // Resumen General
        if ($this->data['type'] == 'General' || $this->data['type'] == 'Ventas') {
            $rows[] = ['RESUMEN DE VENTAS'];
            $rows[] = ['Total Ventas', number_format($this->data['totalSales'], 0)];
            $rows[] = ['Artículos', \App\Helpers\Num::input($this->data['itemsSales'])];
            $rows[] = ['Total Contado', number_format($this->data['totalSalesCash'], 0)];
            $rows[] = ['Total Crédito', number_format($this->data['totalSalesCredit'], 0)];
            $rows[] = ['Crédito Pagado', number_format($this->data['totalCreditPaid'], 0)];
            $rows[] = ['Crédito Pendiente', number_format($this->data['totalCreditPending'], 0)];
            $rows[] = [];
        }

        if ($this->data['type'] == 'General' || $this->data['type'] == 'Compras') {
            $rows[] = ['RESUMEN DE COMPRAS'];
            $rows[] = ['Total Compras', number_format($this->data['totalPurchases'], 0)];
            $rows[] = ['Artículos', \App\Helpers\Num::input($this->data['itemsPurchases'])];
            $rows[] = ['Total Contado', number_format($this->data['totalPurchasesCash'], 0)];
            $rows[] = ['Total Crédito', number_format($this->data['totalPurchasesCredit'], 0)];
            $rows[] = ['Crédito Pagado', number_format($this->data['totalCreditPurchasePaid'], 0)];
            $rows[] = ['Crédito Pendiente', number_format($this->data['totalCreditPurchasePending'], 0)];
            $rows[] = [];
        }

        if ($this->data['type'] == 'General') {
            $rows[] = ['GASTOS EXTRAS'];
            $rows[] = ['Total Gastos', number_format($this->data['totalExtraExpenses'], 0)];
            $rows[] = [];
        }

        if (($this->data['type'] == 'General' || $this->data['type'] == 'Utilidades') && isset($this->data['utilityGross'])) {
            $rows[] = ['UTILIDADES (VENTAS - COSTO DE LO VENDIDO)'];
            $rows[] = ['Ventas con Costo', number_format($this->data['utilityRevenue'], 0)];
            $rows[] = ['Costo de lo Vendido', number_format($this->data['utilityCost'], 0)];
            $rows[] = ['Utilidad Bruta', number_format($this->data['utilityGross'], 0)];
            $rows[] = ['Margen', number_format($this->data['utilityMargin'], 1) . '%'];
            if (isset($this->data['totalExtraExpenses'])) {
                $rows[] = ['Gastos Extras', number_format($this->data['totalExtraExpenses'], 0)];
                $rows[] = ['Utilidad Neta (- Gastos Extras)', number_format($this->data['utilityGross'] - $this->data['totalExtraExpenses'], 0)];
            }
            if ($this->data['utilityUncostedProducts'] > 0) {
                $rows[] = ['Ventas sin costo (excluidas)', number_format($this->data['utilityUncostedRevenue'], 0), $this->data['utilityUncostedProducts'] . ' producto(s) sin costo'];
            }
            $rows[] = [];
        }

        if ($this->data['type'] == 'Utilidades' && !empty($this->data['utilityByCategory'])) {
            $rows[] = ['UTILIDAD POR CATEGORÍA'];
            $rows[] = ['Categoría', 'Ventas', 'Costo', 'Utilidad', 'Margen'];
            foreach ($this->data['utilityByCategory'] as $row) {
                $rows[] = [
                    $row['category'],
                    number_format($row['revenue'], 0),
                    number_format($row['cost'], 0),
                    number_format($row['utility'], 0),
                    number_format($row['margin'], 1) . '%',
                ];
            }
            $rows[] = [];
        }

        if ($this->data['type'] == 'Utilidades' && !empty($this->data['utilityProducts'])) {
            $rows[] = ['UTILIDAD POR PRODUCTO'];
            $rows[] = ['Producto', 'Categoría', 'Cantidad', 'Ventas', 'Costo', 'Utilidad', 'Margen'];
            foreach ($this->data['utilityProducts'] as $row) {
                $rows[] = [
                    $row['product'],
                    $row['category'],
                    $row['quantity'],
                    number_format($row['revenue'], 0),
                    number_format($row['cost'], 0),
                    number_format($row['utility'], 0),
                    number_format($row['margin'], 1) . '%',
                ];
            }
            $rows[] = [];
        }

        if ($this->data['type'] == 'Utilidades' && !empty($this->data['utilityUncostedList'])) {
            $rows[] = ['VENDIDOS SIN COSTO CARGADO (EXCLUIDOS DE LA UTILIDAD)'];
            $rows[] = ['Producto', 'Cantidad', 'Ventas excluidas'];
            foreach ($this->data['utilityUncostedList'] as $row) {
                $rows[] = [$row['product'], $row['quantity'], number_format($row['revenue'], 0)];
            }
            $rows[] = [];
        }

        // Desglose por método de pago (Ventas)
        if ($this->data['type'] == 'Ventas' && !empty($this->data['salesByPaymentMethod'])) {
            $rows[] = ['DESGLOSE POR MÉTODO DE PAGO (VENTAS)'];
            $rows[] = ['Método', 'Contado', 'Crédito Pagado', 'Total Cobrado', 'Crédito Pendiente'];
            
            foreach ($this->data['salesByPaymentMethod'] as $method => $data) {
                $rows[] = [
                    $method,
                    number_format($data['contado'], 0),
                    number_format($data['credito_pagado'], 0),
                    number_format($data['total'], 0),
                    number_format($data['credito_pendiente'], 0),
                ];
            }
            $rows[] = [];
        }

        // Desglose por método de pago (Compras)
        if ($this->data['type'] == 'Compras' && !empty($this->data['purchasesByPaymentMethod'])) {
            $rows[] = ['DESGLOSE POR MÉTODO DE PAGO (COMPRAS)'];
            $rows[] = ['Método', 'Contado', 'Crédito Pagado', 'Total Pagado', 'Crédito Pendiente'];
            
            foreach ($this->data['purchasesByPaymentMethod'] as $method => $data) {
                $rows[] = [
                    $method,
                    number_format($data['contado'], 0),
                    number_format($data['credito_pagado'], 0),
                    number_format($data['total'], 0),
                    number_format($data['credito_pendiente'], 0),
                ];
            }
            $rows[] = [];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    public function properties(): array
    {
        return [
            'creator'    => 'Sistema de RESUMEN',
            'title'      => 'RESUMEN - ' . $this->data['type'],
            'description'=> 'Reporte de RESUMEN',
            'subject'    => 'RESUMEN',
            'keywords'   => 'corte, caja, reporte',
            'category'   => 'Reportes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            },
        ];
    }
}
