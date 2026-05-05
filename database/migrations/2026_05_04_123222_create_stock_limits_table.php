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
        Schema::create('stock_limits', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 30)->unique();
            $table->string('product_name', 200)->nullable();
            $table->string('category_id', 50)->nullable();
            $table->integer('stock_limit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_limits');
    }
};
