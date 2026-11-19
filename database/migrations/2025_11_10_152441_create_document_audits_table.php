<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_audits', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 50); // sale, purchase, etc.
            $table->unsignedBigInteger('source_id'); // ID del documento
            $table->string('action', 30); // CREATED, UPDATED, CANCELLED
            $table->unsignedBigInteger('user_id'); // Usuario que realizó la acción
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_audits');
    }
};
