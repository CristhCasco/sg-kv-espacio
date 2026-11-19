<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCostToSaleDetailsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('sale_details', 'cost')) {
            Schema::table('sale_details', function (Blueprint $table) {
                // Costo unitario (o por kg) del producto al momento de la venta
                $table->decimal('cost', 10, 2)->nullable()->after('price');
            });
        }

        // Ventas anteriores: se aproxima con el costo actual del producto
        DB::statement("
            UPDATE sale_details d
            JOIN products p ON p.id = d.product_id
            SET d.cost = p.cost
            WHERE d.cost IS NULL AND p.cost > 0
        ");
    }

    public function down()
    {
        if (Schema::hasColumn('sale_details', 'cost')) {
            Schema::table('sale_details', function (Blueprint $table) {
                $table->dropColumn('cost');
            });
        }
    }
}
