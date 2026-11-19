<?php

namespace App\Services;

use Throwable;
use App\Models\Product;
use App\Models\DocumentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentCancelService
{
    /**
     * Cancela un documento (venta o compra) de forma segura y auditable.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $document
     * @param  string  $type  "sale" o "purchase"
     * @return array
     */
    public static function cancel($document, string $type): array
    {
        DB::beginTransaction();

        try {
            // 🔹 1. Buscar estado "ANULADA"
            $cancelledStatusId = DocumentStatus::where('name', 'ANULADA')->value('id');
            if (!$cancelledStatusId) {
                throw new \Exception('No se encontró el estado ANULADA.');
            }

            // 🔹 2. Verificar si ya está anulada
            if ($document->document_status_id === $cancelledStatusId) {
                return [
                    'status'  => 'info',
                    'message' => 'Este documento ya fue anulado previamente.'
                ];
            }

            // 🔹 3. Validar detalles
            $details = $document->details ?? collect();
            if ($details->isEmpty()) {
                return [
                    'status'  => 'error',
                    'message' => 'El documento no tiene detalles para anular.'
                ];
            }

            // 🔹 4. Validar fecha: no permitir anular día anterior si ya hay factura activa hoy
            $today = now()->toDateString();
            $invoiceDate = optional($document->created_at)->toDateString();

            $existsInvoiceToday = \App\Models\Sale::whereDate('created_at', $today)
                ->where('id', '!=', $document->id)
                ->whereNotNull('invoice_number')
                ->where('document_status_id', '!=', $cancelledStatusId)
                ->exists();

            if ($invoiceDate !== $today && $existsInvoiceToday) {
                return [
                    'status'  => 'error',
                    'message' => 'No se puede anular una factura de un día anterior porque ya se generó otra factura hoy.'
                ];
            }

            // 🔹 5. Ajustar stock según tipo de documento
            foreach ($details as $detail) {
                if ($type === 'sale') {
                    // Devolver stock
                    Product::where('id', $detail->product_id)->increment('stock', $detail->quantity);
                } elseif ($type === 'purchase') {
                    // Descontar stock
                    Product::where('id', $detail->product_id)->decrement('stock', $detail->quantity);
                }
            }

            // 🔹 6. Cancelar crédito asociado (si existe)
            if (method_exists($document, 'credit') && $document->credit) {
                $document->credit->update([
                    'status'            => 'PAGADO', // solo acepta PENDIENTE o PAGADO
                    'remaining_balance' => 0,
                    'amount_paid'       => $document->credit->total_credit,
                ]);
            }

            // 🔹 7. Cambiar estado general y marcar anulada
            $document->update([
                'document_status_id' => $cancelledStatusId,
                'cancelled_at'       => now(),
                'cancelled_by'       => auth()->id(),
            ]);

            DB::commit();

            return [
                'status'  => 'success',
                'message' => 'Documento anulado correctamente.'
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error al anular documento: ' . $e->getMessage(), [
                'document_id' => $document->id ?? null,
                'type'        => $type,
                'user'        => auth()->user()->name ?? 'desconocido',
            ]);

            return [
                'status'  => 'error',
                'message' => 'Error al anular el documento: ' . $e->getMessage()
            ];
        }
    }
}