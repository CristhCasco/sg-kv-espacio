<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'items',
        'total',
        'cash',
        'change',
        'status',
        'payment_type',
        'payment_method',
        'discount',
        'discount_total',
        'customer_id',
        'user_id',
        'company_id',
        'invoice_type_id',
        'invoice_number',
        'preview_sale_id',

        // 🧾 Campos fiscales
        'total_exentas',
        'total_iva5',
        'total_iva10',
        'total_gravadas_5',   // ✅ NUEVO
        'total_gravadas_10',  // ✅ NUEVO
        'document_status_id', // 
        'cancelled_by',
        'cancelled_at',

        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'total' => 'float',
        'total_exentas' => 'float',
        'total_iva5' => 'float',
        'total_iva10' => 'float',
        'total_gravadas_5' => 'float',
        'total_gravadas_10' => 'float',
    ];

    public function toArray()
    {
        $attributes = parent::toArray();

        // Evitar error si created_at es null
        $attributes['created_at'] = $this->created_at
            ? $this->created_at->timezone('America/Asuncion')->toDateTimeString()
            : null;

        return $attributes;
    }

    // 🔗 Relaciones
    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }


    public function user()
    {
        // Solo devuelve id y nombre del usuario
        return $this->belongsTo(User::class)
            ->select('id', 'name');
    }

    public function credit()
    {
        return $this->hasOne(SaleCredit::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function documentStatus()
    {
        return $this->belongsTo(DocumentStatus::class, 'document_status_id');
    }

    protected static function booted()
    {
        static::creating(function ($sale) {
            if (is_null($sale->document_status_id)) {
                $activeStatusId = \App\Models\DocumentStatus::where('name', 'ACTIVA')->value('id');
                $sale->document_status_id = $activeStatusId;
            }
        });
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }




    // 🔹 (Futuro) método de pagos múltiples
    // public function payments()
    // {
    //     return $this->hasMany(Payment::class);
    // }
}
