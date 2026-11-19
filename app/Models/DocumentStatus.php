<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentStatus extends Model
{
    protected $fillable = ['name'];

    // Opcional: para devolver siempre el nombre en mayúsculas
    public function getNameAttribute($value)
    {
        return strtoupper($value);
    }

    // Relación inversa (opcional si querés listar ventas por estado)
    public function sales()
    {
        return $this->hasMany(Sale::class, 'document_status_id');
    }
}
