<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCustomersForLegalInvoicing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
        {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'document_number')) {
                    $table->string('document_number', 30)->nullable()->after('ci');
                }

                if (!Schema::hasColumn('customers', 'dv')) {
                    $table->string('dv', 2)->nullable()->after('document_number');
                }

                if (!Schema::hasColumn('customers', 'document_type_id')) {
                    $table->foreignId('document_type_id')->nullable()->constrained('document_types');
                }

                if (!Schema::hasColumn('customers', 'tax_condition_id')) {
                    $table->foreignId('tax_condition_id')->nullable()->constrained('tax_conditions');
                }
            });

        }

        public function down()
        {
            Schema::table('customers', function (Blueprint $table) {
                $table->renameColumn('document_number', 'ruc');
                $table->dropColumn(['document_type_id', 'tax_condition_id', 'dv']);
            });
        }

}
