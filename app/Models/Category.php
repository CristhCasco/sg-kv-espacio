<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VatType;

class Category extends Model
{
    use HasFactory;
    use ImageUrlTrait;

    protected $imageFolder = 'categories';

	protected  $fillable = ['name', 'image', 'vat_type_id'];

    // Relación uno a muchos, es decir una categoría puede tener muchos productos
    public function products()
    {
        return $this->hasMany(Product::class);
    }

	public function vatType()
	{
		return $this->belongsTo(VatType::class);
	}

    public function getImagenAttribute()
	{
	

		if($this->image != null)
			return (file_exists('storage/categories/' . $this->image) ? $this->image : 'noimg.jpg');
		else
			return 'noimg.jpg';		

		//método 2
		/*
		if($this->image == null)
		{		
			if(file_exists('storage/categories/' . $this->image))
				return $this->image;
			else
				return 'noimg.jpg';
		} else {
			return 'noimg.jpg';
		}
		*/
		
	}

}
