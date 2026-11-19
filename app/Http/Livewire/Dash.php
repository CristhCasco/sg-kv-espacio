<?php

namespace App\Http\Livewire;

use DateTime;
use App\Models\Sale;
use Livewire\Component;
use App\Models\SaleDetail;
use Illuminate\Support\Facades\DB;

class Dash extends Component
{
    public $salesByMonth_Data = [], $year, $listYears = [], $top5Data = [], $weekSales_Data = [];

    public function mount()
    {
        $this->year = date('Y');
    }

    public function render()
    {
        $this->getWeekSales();
        $this->getTop5();
        $this->getSalesMonth();

        return view('livewire.dashboard.component')
            ->extends('layouts.theme.app')
            ->section('content');
    }

    /**
     * Obtiene los 5 productos más vendidos del año actual
     */
    public function getTop5()
    {
        $this->top5Data = SaleDetail::join('products as p', 'sale_details.product_id', 'p.id')
            ->join('sales as s', 's.id', '=', 'sale_details.sale_id')
            ->leftJoin('document_statuses as ds', 'ds.id', '=', 's.document_status_id')
            ->whereYear('sale_details.created_at', $this->year)
            ->where(function ($q) {
                $q->whereNull('s.document_status_id')
                  ->orWhere('ds.name', '!=', 'ANULADA');
            })
            ->select(DB::raw("p.name AS product, SUM(sale_details.quantity * sale_details.price) AS total"))
            ->groupBy('p.name')
            ->orderBy(DB::raw("SUM(sale_details.quantity * sale_details.price)"), 'desc')
            ->get()
            ->take(5)
            ->toArray();

        // Completa los espacios vacíos con "Sin Ventas"
        $missing = 5 - count($this->top5Data);
        for ($i = 0; $i < $missing; $i++) {
            $this->top5Data[] = ["product" => "Sin Ventas", "total" => 0];
        }
    }

    /**
     * Ventas de los últimos 7 días (sin incluir anuladas)
     */
    public function getWeekSales()
    {
        $dt = new DateTime();
        $this->weekSales_Data = [];

        for ($d = 1; $d <= 7; $d++) {
            $dt->setISODate($dt->format('o'), $dt->format('W'), $d);
            $startDate = $dt->format('Y-m-d') . ' 00:00:00';
            $finishDate = $dt->format('Y-m-d') . ' 23:59:59';

            $wsale = Sale::leftJoin('document_statuses as ds', 'ds.id', '=', 'sales.document_status_id')
                ->where(function ($q) {
                    $q->whereNull('sales.document_status_id')
                      ->orWhere('ds.name', '!=', 'ANULADA');
                })
                ->whereBetween('sales.created_at', [$startDate, $finishDate])
                ->sum('sales.total');

            $this->weekSales_Data[] = $wsale;
        }
    }

    /**
     * Ventas por mes (sin incluir anuladas)
     */
    public function getSalesMonth()
    {
        $this->salesByMonth_Data = [];

        $salesByMonth = DB::select("
            SELECT COALESCE(total, 0) AS total
            FROM (
                SELECT 'january' AS month UNION SELECT 'february' UNION SELECT 'march' UNION SELECT 'april' UNION
                       SELECT 'may' UNION SELECT 'june' UNION SELECT 'july' UNION SELECT 'august' UNION
                       SELECT 'september' UNION SELECT 'october' UNION SELECT 'november' UNION SELECT 'december'
            ) m
            LEFT JOIN (
                SELECT 
                    MONTHNAME(s.created_at) AS month,
                    COUNT(*) AS orders,
                    SUM(s.total) AS total
                FROM sales s
                LEFT JOIN document_statuses ds ON ds.id = s.document_status_id
                WHERE YEAR(s.created_at) = {$this->year}
                AND (s.document_status_id IS NULL OR ds.name != 'ANULADA')
                GROUP BY MONTHNAME(s.created_at), MONTH(s.created_at)
                ORDER BY MONTH(s.created_at)
            ) c ON m.month = c.month;
        ");

        foreach ($salesByMonth as $sale) {
            $this->salesByMonth_Data[] = $sale->total;
        }
    }
}
