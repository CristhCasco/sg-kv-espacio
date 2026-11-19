<?php

namespace App\Exports;

use App\Models\Sale;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BookIvaSalesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $start;
    protected $end;

    public function __construct($start = null, $end = null)
    {
        // Fechas por defecto: mes actual
        $this->start = $start ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end   = $end   ?: Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function collection()
    {
        return Sale::with([
                'customer:id,person,company,name,last_name,document_number,dv',
                'company:id,name,ruc,dv,timbrado_number,timbrado_valid_until,establishment_code,expedition_point'
            ])
            ->whereNotNull('invoice_number') // Solo facturas legales
            ->whereBetween('created_at', [
                $this->start . ' 00:00:00',
                $this->end   . ' 23:59:59'
            ])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'FECHA',
            'TIPO_DOCUMENTO',
            'FACTURA',
            'SERIE',
            'CDC',
            'TIMBRADO',
            'TIMBRADO_VENCIMIENTO',
            'TIPO_DOCUMENTO_PERSONA',
            'DOCUMENTO_PERSONA',
            'NOMBRE_PERSONA',
            'SUBTOTAL_10',
            'SUBTOTAL_05',
            'SUBTOTAL_EXENTAS',
            'LIQUIDACION_IVA_10',
            'LIQUIDACION_IVA_05',
            'LIQUIDACION_IVA_TOTAL',
            'TOTAL_BRUTO',
            'REDONDEO',
            'TOTAL',
            'MONEDA',
            'TOTAL_GS',
            'CONDICION',
            'CUOTAS',
            'CUENTA_10',
            'CUENTA_05',
            'CUENTA_00',
            'CUENTA_DEBE',
            'TRANSACCION',
            'ESTADO_DOCUMENTO',
            'MOTIVO',
        ];
    }

    public function map($sale): array
    {
        // --- Empresa (serie, timbrado)
        $company = $sale->company;
        $serie = '';
        if ($company && $company->establishment_code && $company->expedition_point) {
            $serie = sprintf('%03d-%03d', (int) $company->establishment_code, (int) $company->expedition_point);
        }

        $timbrado = $company->timbrado_number ?? '';
        $timbradoVence = $company && $company->timbrado_valid_until
            ? Carbon::parse($company->timbrado_valid_until)->format('d/m/Y')
            : '';

        // --- Cliente (doc y nombre)
        $docTipo = 'RUC';
        $docNro = '';
        if ($sale->customer) {
            $docNro = trim(($sale->customer->document_number ?? '')) .
                (!empty($sale->customer->dv) ? '-' . trim($sale->customer->dv) : '');

            if (empty($sale->customer->dv)) {
                $docTipo = 'CI';
            }

            $nombre = $sale->customer->company
                ?: trim(($sale->customer->name ?? '') . ' ' . ($sale->customer->last_name ?? ''));
        } else {
            $nombre = 'Consumidor Final';
            $docTipo = 'CI';
            $docNro = '';
        }

        // --- Subtotales gravados y exentas (guardados directamente en sales)
        $sub10 = (float) ($sale->total_gravadas_10 ?? 0); // base gravada 10%
        $sub05 = (float) ($sale->total_gravadas_5 ?? 0);  // base gravada 5%
        $exen  = (float) ($sale->total_exentas ?? 0);     // exentas

        // --- Liquidaciones de IVA
        $liq10 = (float) ($sale->total_iva10 ?? 0);
        $liq05 = (float) ($sale->total_iva5 ?? 0);
        $liqTotal = $liq10 + $liq05;

        // --- Totales
        $totalBruto = $sub10 + $sub05 + $exen;    // base imponible total
        $total      = $totalBruto + $liqTotal;    // total con IVA
        $totalGs    = $total;                     // mismo valor porque es GS
        $redondeo   = 0;

        // --- Otros campos contables
        $tipoDocumento = 'AUTOIMPRESO';
        $cdc           = '';
        $moneda        = 'GS';
        $condicion     = strtoupper($sale->payment_type ?? 'CONTADO');
        $cuotas        = 0;

        // --- Cuentas contables descriptivas
        $cuenta10  = $sub10 > 0 ? 'VENTA DE MERCADERÍAS GRAV. 10%' : '';
        $cuenta05  = $sub05 > 0 ? 'VENTA DE MERCADERÍAS GRAV. 5%'  : '';
        $cuenta00  = $exen  > 0 ? 'VENTA EXENTA'                   : '';
        $cuentaDebe = 'CAJA GUARANÍES';
        $transaccion = '';
        $estadoDoc   = 'ACTIVO';
        $motivo      = '';

        return [
            // FECHA
            Carbon::parse($sale->created_at)->format('d/m/Y'),
            // TIPO_DOCUMENTO
            $tipoDocumento,
            // FACTURA
            $sale->invoice_number,
            // SERIE
            $serie,
            // CDC
            $cdc,
            // TIMBRADO
            $timbrado,
            // TIMBRADO_VENCIMIENTO
            $timbradoVence,
            // TIPO_DOCUMENTO_PERSONA
            $docTipo,
            // DOCUMENTO_PERSONA
            $docNro,
            // NOMBRE_PERSONA
            $nombre,
            // SUBTOTALES
            $sub10,
            $sub05,
            $exen,
            // LIQUIDACIONES IVA
            $liq10,
            $liq05,
            $liqTotal,
            // TOTALES
            $totalBruto,
            $redondeo,
            $total,
            $moneda,
            $totalGs,
            // OTROS CAMPOS
            $condicion,
            $cuotas,
            $cuenta10,
            $cuenta05,
            $cuenta00,
            $cuentaDebe,
            $transaccion,
            $estadoDoc,
            $motivo,
        ];
    }
}
