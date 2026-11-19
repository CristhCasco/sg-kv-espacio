<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreviewSalePayment extends Model
{
    use HasFactory;

    protected $table = 'preview_sale_payments';

    protected $fillable = [
        'preview_sale_id',
        'amount',
        'payment_type',
        'payment_method',
        'delivered_by_user_id',
        'delivery_date',
        'notes'
    ];

    protected $dates = [
        'delivery_date'
    ];

    public function previewSale()
    {
        return $this->belongsTo(PreviewSale::class);
    }

    public function deliveredByUser()
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }
}
