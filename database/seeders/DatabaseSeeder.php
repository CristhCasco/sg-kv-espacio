<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
{
    $this->call([
        CompanySeeder::class,
        DenominationSeeder::class,
        CategorySeeder::class,
        ProductSeeder::class,
        CustomerSeeder::class,
        //SupplierSeeder::class,
        UserSeeder::class,
    ]);
}

}
