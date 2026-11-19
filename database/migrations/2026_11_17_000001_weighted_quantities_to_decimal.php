<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class WeightedQuantitiesToDecimal extends Migration
{
    /**
     * Run the migrations.
     *
     * Las cantidades y el stock estaban como INT, asi que los productos por
     * peso se redondeaban al guardar (0,1 kg quedaba en 0). Se pasan a
     * decimal(15,3); los productos por unidad siguen guardandose enteros.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE sale_details MODIFY quantity DECIMAL(15,3) NOT NULL');
        DB::statement('ALTER TABLE purchase_details MODIFY quantity DECIMAL(15,3) NOT NULL');
        DB::statement('ALTER TABLE products MODIFY stock DECIMAL(15,3) NOT NULL');
        DB::statement('ALTER TABLE products MODIFY min_stock DECIMAL(15,3) NOT NULL');

        // Recupera el peso real de las ventas ya redondeadas a partir del subtotal
        DB::statement("
            UPDATE sale_details sd
            JOIN products p ON p.id = sd.product_id
            SET sd.quantity = ROUND(sd.sub_total / (sd.price - COALESCE(sd.manual_discount, 0)), 3)
            WHERE p.is_weighted = 1
              AND sd.price - COALESCE(sd.manual_discount, 0) > 0
              AND sd.sub_total <> (sd.price - COALESCE(sd.manual_discount, 0)) * sd.quantity
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE sale_details MODIFY quantity INT NOT NULL');
        DB::statement('ALTER TABLE purchase_details MODIFY quantity INT NOT NULL');
        DB::statement('ALTER TABLE products MODIFY stock INT NOT NULL');
        DB::statement('ALTER TABLE products MODIFY min_stock INT NOT NULL');
    }
}
