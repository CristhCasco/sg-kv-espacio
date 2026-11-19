<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class WidenInventoryQuantitiesDecimal extends Migration
{
    /**
     * Run the migrations.
     *
     * decimal(10,3) llega hasta 9.999.999,999 y hay productos con stock
     * cercano a 1.000 millones: el conteo de inventario fallaba con esos
     * productos. Se igualan a products.stock, que es decimal(15,3).
     *
     * @return void
     */
    public function up()
    {
        foreach (['inventories', 'inventory_differences'] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY quantity_excel DECIMAL(15,3) NOT NULL");
            DB::statement("ALTER TABLE {$table} MODIFY quantity_counted DECIMAL(15,3) NULL");
        }
        DB::statement('ALTER TABLE inventory_differences MODIFY difference DECIMAL(15,3) NOT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (['inventories', 'inventory_differences'] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY quantity_excel DECIMAL(10,3) NOT NULL");
            DB::statement("ALTER TABLE {$table} MODIFY quantity_counted DECIMAL(10,3) NULL");
        }
        DB::statement('ALTER TABLE inventory_differences MODIFY difference DECIMAL(10,3) NOT NULL');
    }
}
