<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSettingsFieldsToCompaniesTable extends Migration
{
    /**
     * Campos editables desde la pantalla de Configuración.
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'email')) {
                $table->string('email', 150)->nullable();               // correo de la empresa
            }
            if (! Schema::hasColumn('companies', 'city')) {
                $table->string('city', 100)->nullable();                // ciudad
            }
            if (! Schema::hasColumn('companies', 'economic_activity')) {
                $table->string('economic_activity', 255)->nullable();   // actividad económica (factura)
            }
            if (! Schema::hasColumn('companies', 'timbrado_valid_from')) {
                $table->date('timbrado_valid_from')->nullable();        // inicio de vigencia del timbrado
            }
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['email', 'city', 'economic_activity', 'timbrado_valid_from']);
        });
    }
}
