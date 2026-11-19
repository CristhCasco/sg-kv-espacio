<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();  // CI, RUC, PAS, DNI, EXT
            $table->string('name', 100);           // Descripción legible
            $table->string('set_code', 10)->nullable(); // Código oficial de la SET (para factura electrónica)
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 🔹 Datos iniciales (Paraguay)
        DB::table('document_types')->insert([
            ['code' => 'CI', 'name' => 'Cédula de Identidad', 'set_code' => '1', 'active' => true],
            ['code' => 'RUC', 'name' => 'Registro Único del Contribuyente', 'set_code' => '2', 'active' => true],
            ['code' => 'PAS', 'name' => 'Pasaporte', 'set_code' => '3', 'active' => true],
            ['code' => 'EXT', 'name' => 'Documento Extranjero', 'set_code' => '4', 'active' => true],
            ['code' => 'SIN', 'name' => 'Sin Documento', 'set_code' => '5', 'active' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
