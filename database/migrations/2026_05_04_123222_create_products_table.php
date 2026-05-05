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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('prod_id', 50)->unique();
            $table->string('code', 30)->index();
            $table->string('name', 200);
            $table->string('category_id', 50)->index();
            $table->text('colors_json')->nullable();
            $table->string('dimensions', 200)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('price_display', 30)->nullable();
            $table->text('description')->nullable();
            $table->string('unit', 50)->default('per event');
            $table->tinyInteger('is_poa')->default(0);
            $table->tinyInteger('is_override')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
