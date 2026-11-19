<?php

namespace App\Traits;

// URL lista para <img>: la imagen subida o el respaldo de public/ (storage/app/public no está en git,
// por eso el "noimg.jpg" que devolvían los modelos no existía y se veía la imagen rota)
trait ImageUrlTrait
{
    public function getImagenUrlAttribute()
    {
        $path = 'storage/' . $this->imageFolder . '/' . $this->image;

        return $this->image && file_exists(public_path($path))
            ? asset($path)
            : asset('assets/img/noimg.svg');
    }
}
