<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $id = \App\Models\Customer::DEFAULT_OCCASIONAL_ID;
        $exists = DB::table('customers')->where('id', $id)->exists();

        if ($exists) {
            return;
        }

        DB::table('customers')->insert([
            'id' => $id,
            'person' => 'FISICA',
            'name' => 'OCASIONAL',
            'last_name' => 'CLIENTE',
            'ci' => 0,
            'company' => null,
            'ruc' => null,
            'document_number' => '0',
            'dv' => null,
            'address' => 'SIN DIRECCION',
            'phone' => '000000000',
            'birthday' => null,
            'email' => null,
            'image' => null,
            'credit_limit' => 0,
            'document_type_id' => null,
            'tax_condition_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No se elimina en rollback para evitar borrar datos en ambientes productivos.
    }
};
