<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVatTypesAndAddToCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vat_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->unsignedTinyInteger('rate')->unique();
            $table->timestamps();
        });

        DB::table('vat_types')->insert([
            ['name' => 'EXENTA', 'rate' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'IVA 5%', 'rate' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'IVA 10%', 'rate' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('vat_type_id')->nullable()->after('name');
            $table->foreign('vat_type_id')->references('id')->on('vat_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['vat_type_id']);
            $table->dropColumn('vat_type_id');
        });

        Schema::dropIfExists('vat_types');
    }
}
