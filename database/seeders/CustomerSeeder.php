<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        Customer::updateOrCreate(
            ['ruc' => '6560887'],
            [
                'person' => 'JURIDICA',
                'name' => 'Cris Info',
                'last_name' => 'S.A.',
                'ci' => 6560887,
                'company' => 'Cris Info',
                'ruc' => '6560887',
                'dv' => '9',
                'address' => 'Ciudad del Este',
                'phone' => '0983668960',
                'birthday' => Carbon::createFromFormat('d/m/Y', '01/01/2020'),
                'email' => 'ventas@crisinfo.com',
                'image' => 'https://dummyimage.com/200x150/5c5756/fff',
                'document_number' => '6560887',
                'document_type_id' => null, // si aún no tienes document_types
                'tax_condition_id' => null, // si aún no tienes tax_conditions
            ]
        );
    }
}
