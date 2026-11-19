<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El celular del cliente pasa a ser opcional.
 * Se usa SQL directo porque el proyecto no tiene doctrine/dbal para ->change().
 */
class MakeCustomersPhoneNullable extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE customers MODIFY phone VARCHAR(255) NULL');
    }

    public function down()
    {
        DB::table('customers')->whereNull('phone')->update(['phone' => '']);
        DB::statement('ALTER TABLE customers MODIFY phone VARCHAR(255) NOT NULL');
    }
}
