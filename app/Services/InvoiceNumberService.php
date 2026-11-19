<?php

namespace App\Services;

use App\Models\Company;
use App\Models\DocumentStatus;
use Illuminate\Support\Facades\DB;
use Exception;

class InvoiceNumberService
{
    public static function generate(Company $company)
    {
        return DB::transaction(function () use ($company) {

            if (!$company->timbrado_number) {
                throw new Exception('No hay timbrado activo configurado.');
            }
            if (now()->gt($company->timbrado_valid_until)) {
                throw new Exception('El timbrado ha vencido.');
            }
            if ($company->invoice_current >= $company->invoice_end) {
                throw new Exception('Rango de timbrado agotado.');
            }

            $cancelledStatusId = DocumentStatus::where('name', 'ANULADA')->value('id');
            $today = now()->toDateString();

            // Mayor factura ACTIVA
            $lastActive = \App\Models\Sale::where('company_id', $company->id)
                ->whereNotNull('invoice_number')
                ->when($cancelledStatusId, fn($q) =>
                    $q->where(function ($qq) use ($cancelledStatusId) {
                        $qq->where('document_status_id', '!=', $cancelledStatusId)
                           ->orWhereNull('document_status_id');
                    })
                )
                ->orderByDesc('id')
                ->value('invoice_number');

            $base = max($company->invoice_current ?? 0, self::toNumber($lastActive) ?? 0);

            // Números anulados HOY en el orden en que se anularon (cancelled_at ASC)
            $cancelledToday = \App\Models\Sale::where('company_id', $company->id)
                ->where('document_status_id', $cancelledStatusId)
                ->whereDate('cancelled_at', $today)
                ->whereNotNull('invoice_number')
                ->orderBy('cancelled_at', 'asc')
                ->pluck('invoice_number')
                ->map(fn($n) => self::toNumber($n))
                ->filter()
                ->values();

            $next = null;
            // Tomar el primer anulado de hoy que no esté activo
            foreach ($cancelledToday as $cand) {
                $isActive = \App\Models\Sale::where('company_id', $company->id)
                    ->where('invoice_number', 'LIKE', "%{$cand}")
                    ->when($cancelledStatusId, fn($q) =>
                        $q->where(function ($qq) use ($cancelledStatusId) {
                            $qq->where('document_status_id', '!=', $cancelledStatusId)
                               ->orWhereNull('document_status_id');
                        })
                    )
                    ->exists();
                if (!$isActive) {
                    $next = $cand;
                    break;
                }
            }

            // Si no hay anuladas disponibles, usar correlativo
            if (is_null($next)) {
                $next = $base + 1;
            }

            // Seguridad: si está activo, saltar al correlativo
            $existsActive = \App\Models\Sale::where('company_id', $company->id)
                ->where('invoice_number', 'LIKE', "%{$next}")
                ->when($cancelledStatusId, fn($q) =>
                    $q->where(function ($qq) use ($cancelledStatusId) {
                        $qq->where('document_status_id', '!=', $cancelledStatusId)
                           ->orWhereNull('document_status_id');
                    })
                )
                ->exists();

            if ($existsActive) {
                $next = $base + 1;
            }

            // Mantener el contador en el mayor activo/emitido
            $company->invoice_current = max($base, $next);
            $company->save();

            $num = str_pad($next, 7, '0', STR_PAD_LEFT);
            return "{$company->establishment_code}-{$company->expedition_point}-{$num}";
        });
    }

    private static function toNumber($invoice): ?int
    {
        if (!$invoice) return null;
        preg_match('/(\d{7})$/', $invoice, $m);
        return isset($m[1]) ? (int)$m[1] : null;
    }
}