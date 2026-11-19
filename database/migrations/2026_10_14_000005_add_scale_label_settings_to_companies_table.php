<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScaleLabelSettingsToCompaniesTable extends Migration
{
    /**
     * Formato de las etiquetas de la balanza (Configuración → Balanza).
     * Valores por defecto obtenidos de etiquetas reales de la balanza del cliente:
     *   peso:   2 | 000001 | 00550 | 7  → producto "1", 550 g
     *   unidad: 1 | 000002 | 03000 | 4  → producto "2", 3 unidades
     * Las posiciones son 1-based (el primer dígito de la etiqueta es la posición 1).
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'scale_enabled')) {
                $table->boolean('scale_enabled')->default(true);                   // leer etiquetas de balanza en el POS
                $table->unsignedTinyInteger('scale_barcode_length')->default(13);  // largo de la etiqueta
                $table->string('scale_weight_prefix', 5)->nullable()->default('2'); // prefijo de etiquetas de peso
                $table->string('scale_unit_prefix', 5)->nullable()->default('1');   // prefijo de etiquetas por unidad
                $table->unsignedTinyInteger('scale_code_start')->default(2);       // código del producto (PLU)
                $table->unsignedTinyInteger('scale_code_length')->default(6);
                $table->unsignedTinyInteger('scale_value_start')->default(8);      // peso o cantidad
                $table->unsignedTinyInteger('scale_value_length')->default(5);
                $table->unsignedTinyInteger('scale_weight_decimals')->default(3);  // 00550 → 0,550 kg
                $table->unsignedTinyInteger('scale_unit_decimals')->default(3);    // 03000 → 3 unidades
            }
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'scale_enabled', 'scale_barcode_length', 'scale_weight_prefix', 'scale_unit_prefix',
                'scale_code_start', 'scale_code_length', 'scale_value_start', 'scale_value_length',
                'scale_weight_decimals', 'scale_unit_decimals',
            ]);
        });
    }
}
