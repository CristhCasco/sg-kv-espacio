<?php

namespace App\Services;

use App\Helpers\Num;
use App\Helpers\NumberToWords;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleCreditPayment;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Datos para PrinterNinja (protocolo print://).
 *
 * Formato de siempre: partes JSON separadas por "|", comprimidas con gzcompress y en base64.
 * Partes 0-5: venta, detalle, cliente, usuario, tamaño y empresa (las lee la versión vieja del programa).
 * Parte 6: el ticket ya armado línea por línea (v2). El sistema hace todos los cálculos y
 * alineaciones; PrinterNinja solo envía esas líneas a la impresora.
 */
class PrinterNinjaService
{
    /** Caracteres por línea según el ancho del papel (fuente A de ESC/POS). */
    const CHARS_PER_LINE = [80 => 48, 58 => 32];

    const BRAND = 'www.crisinfo.com.py';

    private $width;
    private $lines = [];

    public static function salePayload(Sale $sale)
    {
        $sale->loadMissing('details.product', 'customer', 'user');
        $company = Company::current();
        $size = Company::ticketWidth();

        $products = SaleTicketService::products($sale);
        $totals = SaleTicketService::totals($sale, $products);

        $ticket = (new self($size))->saleTicket($sale, $company, $products, $totals);

        $parts = [
            json_encode([
                'id'           => $sale->id,
                'user_id'      => $sale->user_id,
                'customer_id'  => $sale->customer_id,
                'total'        => (int) $totals['total'],
                'items'        => (int) round($sale->items),
                'status'       => $sale->status,
                'payment_type' => $sale->payment_type,
                'cash'         => (int) $totals['cash'],
                'change'       => (int) $totals['change'],
                'created_at'   => Carbon::parse($sale->created_at)->format('Y-m-d H:i:s'),
            ]),
            json_encode($products->map(function ($p) {
                return [
                    'quantity'  => $p['quantity'],
                    'price'     => $p['final_price'],
                    'sub_total' => $p['subtotal'],
                    'product'   => ['name' => $p['name']],
                ];
            })->values()),
            json_encode([
                'id'        => optional($sale->customer)->id,
                'name'      => optional($sale->customer)->name,
                'last_name' => optional($sale->customer)->last_name,
            ]),
            json_encode(['id' => optional($sale->user)->id, 'name' => optional($sale->user)->name]),
            json_encode(['size' => $size, 'type' => 'receipt']),
            json_encode([
                'name'    => optional($company)->name,
                'address' => optional($company)->address,
                'phone'   => optional($company)->phone,
                'ruc'     => optional($company)->ruc,
            ]),
            json_encode(self::withVersion($ticket), JSON_UNESCAPED_UNICODE),
        ];

        return self::pack($parts);
    }

    public static function purchasePayload(Purchase $compra)
    {
        $compra->loadMissing('details.product', 'supplier', 'user');
        $size = Company::ticketWidth();
        $data = PurchaseTicketService::data($compra, Company::current());

        return self::documentPayload('purchase', $size, (new self($size))->purchaseTicket($data));
    }

    public static function creditPaymentPayload(SaleCreditPayment $payment)
    {
        $payment->loadMissing('credit.sale.customer', 'user');
        $size = Company::ticketWidth();
        $data = CreditReceiptService::data($payment);

        return self::documentPayload('credit_payment', $size, (new self($size))->creditPaymentTicket($data, CreditReceiptService::verifyUrl($payment)));
    }

    /** Factura legal ($summary = false) o factura sin detalle ($summary = true). */
    public static function invoicePayload($saleId, $summary = false)
    {
        [$sale, $company] = InvoiceService::sale($saleId, $summary);
        $size = Company::ticketWidth();

        return self::documentPayload($summary ? 'invoice_summary' : 'invoice', $size, (new self($size))->invoiceTicket($sale, $company));
    }

    // Documentos que no son ventas: las partes 0-5 van vacías y el tipo no es "receipt",
    // así la versión vieja del programa no imprime nada en lugar de un ticket equivocado
    private static function documentPayload($type, $size, array $ticket)
    {
        return self::pack([
            '{}', '[]', '{}', '{}',
            json_encode(['size' => $size, 'type' => $type]),
            '{}',
            json_encode(self::withVersion($ticket), JSON_UNESCAPED_UNICODE),
        ]);
    }

    // Versión del sistema y mínima de PrinterNinja: si la caja tiene una versión más vieja,
    // PrinterNinja avisa que hay que actualizarlo en lugar de imprimir mal (config/app.php)
    private static function withVersion(array $ticket)
    {
        return $ticket + [
            'sys'       => config('app.version'),
            'min_ninja' => config('app.printerninja_min'),
        ];
    }

    private static function pack(array $parts)
    {
        // "|" separa las partes: dentro de un texto (nombre de producto, pie) se cambia por "/"
        $parts = array_map(function ($part) {
            return str_replace('|', '/', $part);
        }, $parts);

        return base64_encode(gzcompress(implode('|', $parts), 9));
    }

    private function __construct($size)
    {
        $this->width = self::CHARS_PER_LINE[$size] ?? 48;
    }

    private function purchaseTicket(array $d)
    {
        $gs = function ($n) {
            return 'Gs. ' . number_format($n, 0, ',', '.');
        };

        $this->text($d['empresa']['name'], 'c', true, true);
        if (!empty($d['empresa']['ruc'])) {
            $this->text('RUC: ' . $d['empresa']['ruc'] . (empty($d['empresa']['dv']) ? '' : '-' . $d['empresa']['dv']), 'c');
        }
        $this->text('Tel: ' . $d['empresa']['phone'], 'c');
        $this->separator('=');

        $this->text('Comprobante de compra', 'c', true);
        $this->text($d['fecha'], 'c');
        $this->separator();
        $this->text('Proveedor: ' . $d['proveedor']);
        $this->text('Compra: #' . $d['compra_id']);
        $this->text('Tipo: ' . $d['tipo_pago']);
        $this->separator();

        // Productos en columnas, igual que la factura: CANT | DESCRIPCIÓN | P.U. | SUBT
        $w = $this->itemColumns();
        $indent = str_repeat(' ', $w[0] + 1);
        $this->columns(['Cant', 'Descripción', 'P.U.', 'SubT'], $w, true);
        $this->separator();
        foreach ($d['productos'] as $p) {
            $this->columns([Num::qty($p['cantidad']), $p['nombre'], number_format($p['precio_base'], 0, ',', '.'), number_format($p['subtotal_base'], 0, ',', '.')], $w);
            if ($p['descuento_unit'] > 0) {
                $this->row($indent . 'Descuento unitario', '-' . number_format($p['descuento_unit'], 0, ',', '.'));
                $this->row($indent . 'Total descuento', '-' . number_format($p['descuento_total'], 0, ',', '.'));
            }
        }
        $this->separator();

        $this->row('Ítems:', Num::qty($d['total_items']));
        if ($d['ahorro_total'] > 0) {
            $this->row('Total sin descuento:', $gs($d['total_sin_descuento']));
            $this->row('Ahorro total:', $gs($d['ahorro_total']));
            $this->row('Total a pagar:', $gs($d['total']), true, true);
        } else {
            $this->row('Total:', $gs($d['total']), true, true);
        }

        if ($d['tipo_pago'] === 'CREDITO') {
            $this->row('Anticipo:', $gs($d['efectivo']));
            $this->row('Saldo pendiente:', $gs($d['saldo_pendiente']));
            $this->row('Estado:', $d['saldo_pendiente'] <= 0 ? 'PAGADO' : 'PENDIENTE');
        } else {
            $this->row('Pagado:', $gs($d['efectivo']));
            if ($d['vuelto'] > 0) {
                $this->row('Vuelto:', $gs($d['vuelto']));
            }
        }
        $this->separator();
        // Igual que el PDF: quien lo imprime
        $this->text('Atendido por: ' . (optional(auth()->user())->name ?? $d['usuario']));
        $this->separator();

        foreach ($d['company']->ticketFooterLines() as $line) {
            $this->text($line, 'c');
        }
        $this->text('Desarrollado por', 'c');
        $this->text(self::BRAND, 'c', true);

        return ['v' => 2, 'doc' => 'purchase', 'width' => $this->width, 'lines' => $this->lines];
    }

    private function creditPaymentTicket(array $d, $verifyUrl)
    {
        $gs = function ($n) {
            return 'Gs. ' . number_format($n, 0, ',', '.');
        };
        $company = $d['company'];

        $this->text(optional($company)->name ?? '', 'c', true, true);
        $this->text(optional($company)->address ?? '', 'c');
        if (!empty(optional($company)->ruc)) {
            $this->text('RUC: ' . $company->ruc . '-' . $company->dv, 'c');
        }
        if (!empty(optional($company)->phone)) {
            $this->text('Tel: ' . $company->phone, 'c');
        }
        $this->text('Comprobante de pago', 'c', true);
        $this->text($d['fecha'], 'c');
        $this->separator();

        $this->text('Cliente: ' . $d['cliente']);
        $this->text('Estado: ' . $d['estado_credito']);
        $this->text('Venta: #' . $d['venta_id']);
        $this->separator();
        $this->row('Total crédito:', $gs($d['total_original']));
        $this->row('Pagado:', $gs($d['total_pagado']));
        $this->row('Pendiente:', $gs($d['saldo']), true);
        $this->separator();

        $this->text('Pagos realizados:');
        foreach ($d['pagos'] as $p) {
            $this->row('  - ' . $p['fecha'], $gs($p['monto']));
        }
        $this->separator();
        $this->text('Atendido por: ' . $d['usuario']);
        $this->separator();

        $this->text('');
        $this->text('');
        $label = 'Firma del cliente: ';
        $this->text($label . str_repeat('_', max(10, $this->width - mb_strlen($label))));
        $this->text('');
        $this->separator();

        foreach ($company ? $company->ticketFooterLines('Gracias por su pago') : ['Gracias por su pago'] as $line) {
            $this->text($line, 'c');
        }
        // El QR de verificación ($this->qr($verifyUrl) + "Escanea para verificar") por ahora no se imprime
        $this->separator();
        $this->text('Desarrollado por', 'c');
        $this->text(self::BRAND, 'c', true);
        $this->separator();
        $this->text('Este comprobante fue generado por el sistema.', 'c');
        $this->text('Su validez depende de la verificación en el sistema original.', 'c');

        return ['v' => 2, 'doc' => 'credit_payment', 'width' => $this->width, 'lines' => $this->lines];
    }

    // Mismo contenido que pdf/invoice/invoice_legal.blade.php
    private function invoiceTicket(Sale $sale, $company)
    {
        $n = function ($v) {
            return number_format($v, 0, ',', '.');
        };

        // Empresa y timbrado
        $this->text($company->name, 'c', true, true);
        if (!empty($company->trade_name) && $company->trade_name !== $company->name) {
            $this->text($company->trade_name, 'c');
        }
        if (!empty($company->economic_activity)) {
            $this->text($company->economic_activity, 'c');
        }
        $this->text(($company->address ?? '-') . (!empty($company->city) ? ' - ' . $company->city : ''), 'c');
        if (!empty($company->phone)) {
            $this->text('Tel: ' . $company->phone, 'c');
        }
        $this->text('');
        $this->text('RUC: ' . $company->ruc . '-' . $company->dv, 'c');
        $this->text('Timbrado N°: ' . $company->timbrado_number, 'c');
        if (!empty($company->timbrado_valid_from)) {
            $this->text('Inicio de vigencia: ' . Carbon::parse($company->timbrado_valid_from)->format('d/m/Y'), 'c');
        }
        $this->text('Vencimiento: ' . Carbon::parse($company->timbrado_valid_until)->format('d/m/Y'), 'c');
        $this->separator();
        $this->text('Factura', 'c', true, true);
        $this->text('N° ' . ($sale->invoice_number ?? '---'), 'c', true);
        $this->separator();

        // Cliente
        if ($sale->customer) {
            $cliente = strtoupper(trim($sale->customer->person ?? '')) === 'JURIDICA'
                ? ($sale->customer->company ?? '—')
                : trim($sale->customer->name . ' ' . ($sale->customer->last_name ?? ''));
        } else {
            $cliente = 'Consumidor Final';
        }
        $this->text('Cliente: ' . $cliente);
        $this->text(($sale->customer ? $sale->customer->document_label : 'C.I.') . ': ' . (optional($sale->customer)->full_document ?? '-'));
        $this->text('Dirección: ' . ($sale->customer->address ?? '-'));
        $this->text('Condición: ' . ($sale->payment_type ?? 'CONTADO'));
        $this->text('Fecha: ' . $sale->created_at->format('d/m/Y H:i'));
        $this->separator();

        // Detalle en columnas: CANT | DESCRIPCIÓN | P.U. | SUBT
        $w = $this->itemColumns();
        $this->columns(['Cant', 'Descripción', 'P.U.', 'SubT'], $w, true);
        $this->separator();
        foreach ($sale->details as $item) {
            $quantity = $item->quantity ?? 1;
            $price = $item->price ?? $item->sub_total ?? 0;
            $subtotal = $item->sub_total ?? ($item->price ?? 0) * $quantity;
            $this->columns([Num::qty($quantity), $item->product->name ?? 'CONSUMISIÓN', $n($price), $n($subtotal)], $w);
        }
        $this->separator();

        // Subtotales con IVA incluido y liquidación del IVA (InvoiceService::totals)
        $t = InvoiceService::totals($sale);
        $this->text('Subtotales (IVA incluido)', 'l', true);
        $this->row('  Exentas:', $n($t['exentas']));
        $this->row('  5%:', $n($t['sub5']));
        $this->row('  10%:', $n($t['sub10']));
        $this->row('Total a Pagar:', $n($t['total']) . ' Gs.', true, true);
        $this->separator();
        $this->text('Liquidación del IVA', 'l', true);
        $this->row('  (5%):', $n($t['iva5']));
        $this->row('  (10%):', $n($t['iva10']));
        $this->row('  Total IVA:', $n($t['total_iva']), true);
        $this->separator();

        $this->text('En Letras:', 'c');
        $this->text(Str::upper(NumberToWords::convert($sale->total ?? 0)) . ' GUARANÍES', 'c', true);
        $this->text('');
        foreach ($company->ticketFooterLines() as $line) {
            $this->text($line, 'c', true);
        }
        $this->text(self::BRAND, 'c');

        return ['v' => 2, 'doc' => 'invoice', 'width' => $this->width, 'lines' => $this->lines];
    }

    /**
     * Fila en columnas de ancho fijo; la descripción (columna 2) sigue en las líneas de abajo si no entra.
     * La primera columna va a la izquierda y las numéricas (3 y 4) a la derecha.
     */
    private function columns(array $cells, array $widths, $bold = false)
    {
        $cells = array_map([$this, 'clean'], $cells);
        $desc = explode("\n", $this->wrap($cells[1], $widths[1]));

        foreach ($desc as $i => $part) {
            $line = $i === 0
                ? $this->fit($cells[0], $widths[0], false) . ' ' . $this->fit($part, $widths[1], false)
                    . $this->fit($cells[2], $widths[2], true) . $this->fit($cells[3], $widths[3], true)
                : str_repeat(' ', $widths[0] + 1) . $part;
            $this->lines[] = ['t' => rtrim($line), 'a' => 'l', 'b' => $bold, 'h' => false];
        }
    }

    /** Anchos de CANT, DESCRIPCIÓN, P.U. y SUBT: + 1 espacio tras la cantidad = ancho del papel (48 u 32). */
    private function itemColumns()
    {
        return $this->width === 48 ? [6, 17, 11, 13] : [4, 9, 8, 10];
    }

    private function fit($text, $width, $right)
    {
        $text = mb_substr($text, 0, $width);
        $pad = str_repeat(' ', $width - mb_strlen($text));

        return $right ? $pad . $text : $text . $pad;
    }

    /** Código QR centrado, con el QR nativo de la impresora (ESC/POS). */
    private function qr($data)
    {
        $this->lines[] = ['qr' => (string) $data, 'a' => 'c'];
    }

    private function saleTicket(Sale $sale, $company, $products, array $t)
    {
        $gs = function ($n) {
            return 'Gs. ' . number_format($n, 0, ',', '.');
        };

        // Empresa
        $this->text(optional($company)->name ?? '', 'c', true, true);
        $this->text(optional($company)->address ?? '', 'c');
        if (!empty(optional($company)->ruc)) {
            $this->text('RUC: ' . $company->ruc . (empty($company->dv) ? '' : '-' . $company->dv), 'c');
        }
        if (!empty(optional($company)->phone)) {
            $this->text('Tel: ' . $company->phone, 'c');
        }
        $this->separator('=');

        // Venta
        $this->text('Comprobante de venta', 'c', true);
        $this->text(Carbon::parse($sale->created_at)->format('d/m/Y H:i'), 'c');
        $this->separator();
        $this->text('Cliente: ' . (optional($sale->customer)->name ?? 'Without customer'));
        $this->text('Venta: #' . $sale->id);
        $this->text('Tipo: ' . $sale->payment_type);
        $this->separator();

        // Productos en columnas, igual que la factura: CANT | DESCRIPCIÓN | P.U. | SUBT
        $w = $this->itemColumns();
        $indent = str_repeat(' ', $w[0] + 1);
        $this->columns(['Cant', 'Descripción', 'P.U.', 'SubT'], $w, true);
        $this->separator();
        foreach ($products as $p) {
            $qty = $p['is_weighted'] ? Num::qty($p['quantity']) : number_format($p['quantity'], 0, ',', '.');
            $this->columns([$qty, $p['name'], number_format($p['final_price'], 0, ',', '.'), number_format($p['subtotal'], 0, ',', '.')], $w);
            if ($p['unit_discount'] > 0) {
                $this->row($indent . 'Descuento unitario', '-' . number_format($p['unit_discount'], 0, ',', '.'));
                $this->row($indent . 'Total descuento', '-' . number_format($p['total_discount'], 0, ',', '.'));
            }
        }
        $this->separator();

        // Totales
        $this->row('Ítems:', Num::qty($t['total_items']));
        if ($t['total_savings'] > 0) {
            $this->row('Total sin descuento:', $gs($t['total_without_discount']));
            $this->row('Ahorro total:', $gs($t['total_savings']));
            $this->row('Total a pagar:', $gs($t['total']), true, true);
        } else {
            $this->row('Total:', $gs($t['total']), true, true);
        }
        // Sin gravadas ni IVA: el ticket es un comprobante interno; el IVA va en la factura
        $this->separator();

        // Pago
        if (strtoupper($sale->payment_type) === 'CREDITO') {
            $this->row('Anticipo:', $gs($t['cash']));
            $this->row('Saldo pendiente:', $gs($t['pending_balance']));
            $this->row('Estado:', $t['pending_balance'] <= 0 ? 'PAGADO' : 'PENDIENTE');
        } else {
            $this->row('Pagado:', $gs($t['cash']));
            if ($t['change'] > 0) {
                $this->row('Vuelto:', $gs($t['change']));
            }
        }
        $this->separator();
        $this->text('Atendido por: ' . (optional($sale->user)->name ?? 'Sistema'));
        $this->separator();

        foreach ($company ? $company->ticketFooterLines() : ['Gracias por su compra'] as $line) {
            $this->text($line, 'c');
        }
        $this->text(self::BRAND, 'c', true);

        return ['v' => 2, 'doc' => 'sale', 'width' => $this->width, 'lines' => $this->lines];
    }

    /** Texto con ajuste de línea. a: l|c|r, b: negrita, h: doble alto. */
    private function text($text, $align = 'l', $bold = false, $tall = false)
    {
        $text = $this->clean($text);
        $chunks = $text === '' ? [''] : explode("\n", $this->wrap($text, $this->width));
        foreach ($chunks as $chunk) {
            $this->lines[] = ['t' => $chunk, 'a' => $align, 'b' => $bold, 'h' => $tall];
        }
    }

    /** Etiqueta a la izquierda y valor a la derecha, en una línea si entra. */
    private function row($left, $right, $bold = false, $tall = false)
    {
        // La sangría de las líneas de detalle ("  2 x 5.000") se conserva
        $indent = strlen($left) - strlen(ltrim($left, ' '));
        $left = str_repeat(' ', $indent) . $this->clean($left);
        $right = $this->clean($right);
        $space = $this->width - mb_strlen($left) - mb_strlen($right);

        if ($space < 1) {
            $this->text($left, 'l', $bold, $tall);
            $this->lines[] = ['t' => $right, 'a' => 'r', 'b' => $bold, 'h' => $tall];
            return;
        }

        $this->lines[] = ['t' => $left . str_repeat(' ', $space) . $right, 'a' => 'l', 'b' => $bold, 'h' => $tall];
    }

    private function separator($char = '-')
    {
        $this->lines[] = ['t' => str_repeat($char, $this->width), 'a' => 'l', 'b' => false, 'h' => false];
    }

    // Mayúsculas como el ticket del navegador; sin saltos ni tabulaciones sueltas
    private function clean($text)
    {
        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) $text)));
    }

    private function wrap($text, $width)
    {
        $out = [];
        $line = '';
        foreach (explode(' ', $text) as $word) {
            while (mb_strlen($word) > $width) {
                if ($line !== '') {
                    $out[] = $line;
                    $line = '';
                }
                $out[] = mb_substr($word, 0, $width);
                $word = mb_substr($word, $width);
            }
            if ($line === '') {
                $line = $word;
            } elseif (mb_strlen($line) + 1 + mb_strlen($word) <= $width) {
                $line .= ' ' . $word;
            } else {
                $out[] = $line;
                $line = $word;
            }
        }
        $out[] = $line;

        return implode("\n", $out);
    }
}
