<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreviewSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'created_by_user_id',
        'status',
        'expected_delivery_date',
        'total',
        'notes'
    ];

    protected $dates = [
        'expected_delivery_date',
        'confirmed_at',
        'cancelled_at'
    ];

    // Relaciones
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function details()
    {
        return $this->hasMany(PreviewSaleDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(PreviewSalePayment::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'preview_sale_id');
    }

    // Métodos
    public function isActive()
    {
        return $this->status === 'pending';
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function getTotalPaidAttribute()
    {
        // Sumar pagos desde SaleCreditPayment de las ventas relacionadas
        $creditPayments = SaleCreditPayment::whereHas('credit.sale', function($query) {
            $query->where('preview_sale_id', $this->id);
        })->sum('amount_paid');
        
        // Si no hay créditos, retornar lo que esté en total_paid de la preventa
        // (esto fue configurado al crear la venta)
        return max($creditPayments, $this->attributes['total_paid'] ?? 0);
    }

    public function getPendingPaymentAttribute()
    {
        return $this->total - $this->total_paid;
    }

    public function confirm()
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);
    }

    public function cancel()
    {
        // Restaurar stock
        foreach ($this->details as $detail) {
            $detail->product->increment('stock', $detail->quantity);
            $detail->product->decrement('stock_in_trial', $detail->quantity);
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
    }
}
