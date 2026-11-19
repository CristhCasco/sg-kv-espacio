<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('document_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // ACTIVA, ANULADA
            $table->timestamps();
        });

            DB::table('document_statuses')->insert([
            ['name' => 'ACTIVA', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ANULADA', 'created_at' => now(), 'updated_at' => now()],
        ]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_statuses');
    }
}
