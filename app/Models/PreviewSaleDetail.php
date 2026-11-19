<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreviewSaleDetail extends Model
{
    use HasFactory;

    protected $table = 'preview_sale_details';

    protected $fillable = [
        'preview_sale_id',
        'product_id',
        'quantity',
        'price',
        'notes'
    ];

    public function previewSale()
    {
        return $this->belongsTo(PreviewSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalAttribute()
    {
        return $this->quantity * $this->price;
    }
}
