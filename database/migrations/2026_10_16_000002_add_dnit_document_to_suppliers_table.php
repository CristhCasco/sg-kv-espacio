<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDnitDocumentToSuppliersTable extends Migration
{
    /**
     * Proveedores con el mismo formulario DNIT que clientes:
     * CI / RUC + DV, y empresas sin nombre/apellido/CI (antes eran obligatorios).
     * Sin doctrine/dbal, por eso los cambios de columna van con ALTER directo.
     */
    public function up()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'document_number')) {
                $table->string('document_number', 20)->nullable()->after('ci')->index();
            }
            if (! Schema::hasColumn('suppliers', 'dv')) {
                $table->char('dv', 1)->nullable()->after('document_number');
            }
        });

        DB::statement('ALTER TABLE suppliers MODIFY name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE suppliers MODIFY last_name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE suppliers MODIFY ci INT NULL');
        DB::statement('ALTER TABLE suppliers MODIFY phone VARCHAR(255) NULL');

        // Proveedores existentes: "1234567-8" en ruc -> documento 1234567 y DV 8; si no, el CI
        foreach (DB::table('suppliers')->whereNull('document_number')->get(['id', 'ci', 'ruc']) as $s) {
            $ruc = preg_replace('/[^\d-]/', '', (string) $s->ruc);
            if (preg_match('/^(\d{3,9})-(\d)$/', $ruc, $m)) {
                $data = ['document_number' => $m[1], 'dv' => $m[2]];
            } elseif ($s->ci) {
                $data = ['document_number' => (string) $s->ci];
            } else {
                continue;
            }
            DB::table('suppliers')->where('id', $s->id)->update($data);
        }
    }

    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['document_number']);
            $table->dropColumn(['document_number', 'dv']);
        });
    }
}
