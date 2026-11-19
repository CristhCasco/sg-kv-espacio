<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run()
    {
        // KV Espacio todavía no tiene RUC ni timbrado: completarlos desde Configuración cuando los tenga
        Company::updateOrCreate(
            ['name' => 'KV Espacio'],
            [
                'name' => 'KV Espacio',                    // Nombre o razón social
                'trade_name' => 'KV Espacio',              // Nombre comercial
                'address' => 'Itaipyte, detrás del Colegio Nacional',
                'phone' => '0981568177',
                'ruc' => '',                               // Sin RUC por ahora (columna no admite NULL)
                'dv' => null,
                'timbrado_number' => null,
                'timbrado_valid_until' => null,
                'establishment_code' => null,
                'expedition_point' => null,
                'invoice_start' => null,
                'invoice_end' => null,
                'invoice_current' => 0,
            ]
        );
    }
}
