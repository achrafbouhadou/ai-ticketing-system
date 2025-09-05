<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('subject', 200);
            $table->text('body');

            $table->enum('status', ['open','in_progress','resolved'])->default('open');
            $table->enum('category', ['billing','technical','account','other'])->nullable();

            $table->enum('category_source', ['ai','manual'])->default('ai');
            $table->text('explanation')->nullable();
            $table->decimal('confidence', 3, 2)->nullable();

            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
