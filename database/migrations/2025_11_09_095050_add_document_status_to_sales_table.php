<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1️⃣ Agregar la columna
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'document_status_id')) {
                $table->foreignId('document_status_id')
                    ->nullable()
                    ->after('invoice_number')
                    ->constrained('document_statuses');
            }
        });

        // 2️⃣ Asignar "ACTIVA" por defecto a las ventas actuales
        $activaId = DB::table('document_statuses')->where('name', 'ACTIVA')->value('id');
        if ($activaId) {
            DB::table('sales')->whereNull('document_status_id')->update(['document_status_id' => $activaId]);
        }

        // ⚠️ Evitamos usar DBAL, así que no intentamos cambiar nullable aquí.
        // En lugar de eso, controlaremos en código que siempre se asigne un estado.
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'document_status_id')) {
                $table->dropConstrainedForeignId('document_status_id');
            }
        });
    }
};
