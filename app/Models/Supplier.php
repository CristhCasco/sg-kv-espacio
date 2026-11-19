<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use CristhCasco\SgDnit\Concerns\HasDocumentoPy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    use ImageUrlTrait;
    use HasDocumentoPy;

    protected $imageFolder = 'suppliers';

    protected $fillable = [ 'person', 'name', 'last_name', 'ci', 'document_number', 'dv', 'company', 'ruc', 'address', 'phone', 'birthday', 'email', 'image'];

    // Empresas no tienen nombre/apellido: se muestra la razón social (igual que Customer)
    public function getDisplayNameAttribute()
    {
        $fullName = trim(collect([$this->name, $this->last_name])->filter()->implode(' '));
        $companyName = trim((string) $this->company);

        if (strtoupper(trim((string) $this->person)) === 'JURIDICA') {
            return $companyName ?: $fullName ?: 'Sin proveedor';
        }

        return $fullName ?: $companyName ?: 'Sin proveedor';
    }


    //accessor
    public function getImagenAttribute()
    {	
        if($this->image != null)
            return (file_exists('storage/suppliers/' . $this->image) ? $this->image : 'noimg.jpg');
        else
            return 'noimg.jpg';		
        
    }

}
