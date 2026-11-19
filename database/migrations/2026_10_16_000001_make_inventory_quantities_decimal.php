<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeInventoryQuantitiesDecimal extends Migration
{
    /**
     * Las cantidades eran enteras: un producto por peso contado como 2,5 kg se guardaba como 2.
     * Mismo formato que products.stock (decimal 10,3). Sin doctrine/dbal, por eso ALTER directo.
     */
    public function up()
    {
        foreach (['inventories', 'inventory_differences'] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY quantity_excel DECIMAL(10,3) NOT NULL");
            DB::statement("ALTER TABLE {$table} MODIFY quantity_counted DECIMAL(10,3) NULL");
        }
        DB::statement('ALTER TABLE inventory_differences MODIFY difference DECIMAL(10,3) NOT NULL');
    }

    public function down()
    {
        foreach (['inventories', 'inventory_differences'] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY quantity_excel INT NOT NULL");
            DB::statement("ALTER TABLE {$table} MODIFY quantity_counted INT NULL");
        }
        DB::statement('ALTER TABLE inventory_differences MODIFY difference INT NOT NULL');
    }
}
