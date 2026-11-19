<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'source_id',
        'action',
        'user_id',
        'notes',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
