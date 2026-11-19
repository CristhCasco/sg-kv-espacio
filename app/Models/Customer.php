<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use CristhCasco\SgDnit\Concerns\HasDocumentoPy;

class Customer extends Model
{
    use HasFactory;
    use ImageUrlTrait;

    protected $imageFolder = 'customers';
    use HasDocumentoPy;

    public const DEFAULT_OCCASIONAL_ID = 1;

    protected $fillable = [
        'person',
        'name',
        'last_name',
        'ci',
        'company',
        'ruc',
        'document_number',
        'dv',
        'address',
        'phone',
        'birthday',
        'email',
        'image',
        'credit_limit',
        'document_type_id',
        'tax_condition_id'
    ];

    protected static function booted()
    {
        static::deleting(function (Customer $customer) {
            if ($customer->id === self::DEFAULT_OCCASIONAL_ID) {
                throw ValidationException::withMessages([
                    'customer' => 'El cliente ocasional predeterminado no se puede eliminar.'
                ]);
            }
        });
    }

    public function isDefaultOccasional(): bool
    {
        return (int) $this->id === self::DEFAULT_OCCASIONAL_ID;
    }

    // accessor
    public function getImagenAttribute()
    {	
        if ($this->image != null)
            return (file_exists('storage/customers/' . $this->image) ? $this->image : 'noimg.jpg');
        else
            return 'noimg.jpg';		
    }

    public function getDisplayNameAttribute()
    {
        $personType = strtoupper(trim((string) $this->person));
        $fullName = trim(collect([$this->name, $this->last_name])->filter()->implode(' '));
        $companyName = trim((string) ($this->company ?? ''));

        if ($personType === 'JURIDICA') {
            return $companyName ?: $fullName ?: 'Cliente Ocasional';
        }

        return $fullName ?: $companyName ?: 'Cliente Ocasional';
    }
}
