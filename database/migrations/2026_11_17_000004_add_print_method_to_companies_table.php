<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrintMethodToCompaniesTable extends Migration
{
    /**
     * Método de impresión de tickets y comprobantes (Configuración → Impresión).
     * "browser" (impresión web, por defecto) o "ninja" (programa PrinterNinja).
     */
    public function up()
    {
        if (! Schema::hasColumn('companies', 'print_method')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('print_method', 20)->default('browser');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('companies', 'print_method')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('print_method');
            });
        }
    }
}
