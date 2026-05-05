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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 50)->index();
            $table->decimal('amount', 12, 2);
            $table->string('method', 50); // bank_transfer, mpesa, cash, card
            $table->string('reference', 100)->nullable();
            $table->string('status', 30)->default('pending'); // pending, confirmed, failed
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Setup foreign key manually as orders table has string order_id instead of integer id as the relation key often used in this codebase
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
