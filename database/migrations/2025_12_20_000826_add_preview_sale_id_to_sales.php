<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreviewSaleIdToSales extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('sales', 'preview_sale_id')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('preview_sale_id')->nullable()->constrained('preview_sales')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['preview_sale_id']);
            $table->dropColumn('preview_sale_id');
        });
    }
}
