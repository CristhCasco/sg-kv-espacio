<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaxConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_conditions', function (Blueprint $table) {
        $table->id();
        $table->string('name', 100); // Ej: Contribuyente, No Contribuyente, Exento
        $table->string('code', 10)->nullable(); // C, NC, E
        $table->timestamps();
        });

        DB::table('tax_conditions')->insert([
            ['name' => 'Contribuyente', 'code' => 'C'],
            ['name' => 'No Contribuyente', 'code' => 'NC'],
            ['name' => 'Exento', 'code' => 'E'],
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tax_conditions');
    }
}
