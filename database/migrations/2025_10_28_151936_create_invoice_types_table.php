<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Ej: Ticket, Factura Legal, Factura Electrónica
            $table->boolean('is_electronic')->default(false);
            $table->timestamps();
        });

        DB::table('invoice_types')->insert([
            ['name' => 'Ticket', 'is_electronic' => false],
            ['name' => 'Factura Legal', 'is_electronic' => false],
            ['name' => 'Factura Electrónica', 'is_electronic' => true],
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_types');
    }
}
