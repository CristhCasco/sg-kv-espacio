<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLogoAndPrintSettingsToCompaniesTable extends Migration
{
    /**
     * Logo y configuración de impresión (pantalla de Configuración).
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'logo')) {
                $table->string('logo', 255)->nullable();                         // archivo en storage/app/public/company
            }
            if (! Schema::hasColumn('companies', 'ticket_width')) {
                $table->unsignedTinyInteger('ticket_width')->default(80);        // ancho del papel en mm (58 u 80)
            }
            if (! Schema::hasColumn('companies', 'ticket_show_logo')) {
                $table->boolean('ticket_show_logo')->default(true);              // imprimir logo en tickets
            }
            if (! Schema::hasColumn('companies', 'ticket_footer')) {
                $table->text('ticket_footer')->nullable();                       // texto al pie del ticket
            }
            if (! Schema::hasColumn('companies', 'print_after_sale')) {
                $table->string('print_after_sale', 10)->default('ask');          // ask | ticket | none
            }
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['logo', 'ticket_width', 'ticket_show_logo', 'ticket_footer', 'print_after_sale']);
        });
    }
}
