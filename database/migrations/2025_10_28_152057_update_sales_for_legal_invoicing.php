<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSalesForLegalInvoicing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('invoice_type_id')->nullable()->constrained('invoice_types');
            $table->string('invoice_number', 15)->nullable(); // Ej: 001-002-0001140
            $table->decimal('total_exentas', 15, 2)->default(0);
            $table->decimal('total_iva5', 15, 2)->default(0);
            $table->decimal('total_iva10', 15, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['invoice_type_id']);
            $table->dropColumn(['invoice_number', 'total_exentas', 'total_iva5', 'total_iva10']);
        });
    }

}
