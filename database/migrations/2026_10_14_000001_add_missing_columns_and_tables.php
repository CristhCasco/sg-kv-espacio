<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsAndTables extends Migration
{
    /**
     * Run the migrations.
     *
     * Columnas y tablas que el cÃ³digo usa pero que en algunas bases se
     * agregaron a mano (o nunca llegaron). Todo se crea solo si falta, asÃ­
     * sirve tanto para una base nueva como para las bases existentes.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('products', 'stock_in_trial')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('stock_in_trial', 10, 3)->default(0);
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable();
            }
            if (!Schema::hasColumn('sales', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('sales', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable();
            }
        });

        Schema::table('sale_details', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_details', 'manual_price')) {
                $table->decimal('manual_price', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('sale_details', 'manual_discount')) {
                $table->decimal('manual_discount', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('sale_details', 'manual_discount_percent')) {
                $table->decimal('manual_discount_percent', 15, 2)->nullable();
            }
        });

        if (!Schema::hasColumn('purchases', 'due_date')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->date('due_date')->nullable();
            });
        }

        if (!Schema::hasColumn('purchase_credits', 'due_date')) {
            Schema::table('purchase_credits', function (Blueprint $table) {
                $table->date('due_date')->nullable();
            });
        }

        if (!Schema::hasTable('inventories')) {
            Schema::create('inventories', function (Blueprint $table) {
                $table->id();
                $table->string('barcode');
                $table->string('description');
                $table->integer('quantity_excel');
                $table->integer('quantity_counted')->nullable();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_differences')) {
            Schema::create('inventory_differences', function (Blueprint $table) {
                $table->id();
                $table->string('barcode');
                $table->string('description');
                $table->integer('quantity_excel');
                $table->integer('quantity_counted')->nullable();
                $table->integer('difference');
                $table->foreignId('user_id')->constrained('users');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Sin reversa: en las bases existentes estas columnas y tablas ya
        // estaban antes de esta migraciÃ³n y tienen datos.
    }
}
