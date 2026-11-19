<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreviewSalesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * Estas tablas se crearon a mano en las bases existentes; por eso
     * cada una se crea solo si todavía no existe.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('preview_sales')) {
            Schema::create('preview_sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers');
                $table->foreignId('created_by_user_id')->constrained('users');
                $table->string('status')->default('pending');
                $table->date('expected_delivery_date')->nullable();
                $table->decimal('total', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('preview_sale_details')) {
            Schema::create('preview_sale_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('preview_sale_id')->constrained('preview_sales')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products');
                $table->decimal('quantity', 10, 3);
                $table->decimal('price', 10, 2);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('preview_sale_payments')) {
            Schema::create('preview_sale_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('preview_sale_id')->constrained('preview_sales')->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->string('payment_type');
                $table->string('payment_method');
                $table->foreignId('delivered_by_user_id')->nullable()->constrained('users');
                $table->timestamp('delivery_date')->useCurrent();
                $table->text('notes')->nullable();
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
        Schema::dropIfExists('preview_sale_payments');
        Schema::dropIfExists('preview_sale_details');
        Schema::dropIfExists('preview_sales');
    }
}
