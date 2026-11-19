<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCompaniesForLegalInvoicing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
        {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('trade_name', 255)->nullable();         // nombre comercial
                $table->string('dv', 2)->nullable();                   // dígito verificador del RUC
                $table->string('timbrado_number', 20)->nullable();     // timbrado actual
                $table->date('timbrado_valid_until')->nullable();      // vencimiento del timbrado
                $table->string('establishment_code', 3)->nullable();   // ej: 001
                $table->string('expedition_point', 3)->nullable();     // ej: 002
                $table->integer('invoice_start')->nullable();          // rango inicial
                $table->integer('invoice_end')->nullable();            // rango final
                $table->integer('invoice_current')->default(0);        // última factura emitida
            });
        }

        public function down()
        {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn([
                    'trade_name', 'dv', 'timbrado_number', 'timbrado_valid_until',
                    'establishment_code', 'expedition_point',
                    'invoice_start', 'invoice_end', 'invoice_current'
                ]);
            });
}

}
