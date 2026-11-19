<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'total_gravadas_10')) {
                $table->decimal('total_gravadas_10', 15, 2)->default(0)->after('total_iva10');
            }
            if (!Schema::hasColumn('sales', 'total_gravadas_5')) {
                $table->decimal('total_gravadas_5', 15, 2)->default(0)->after('total_iva5');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['total_gravadas_10', 'total_gravadas_5']);
        });
    }
};
