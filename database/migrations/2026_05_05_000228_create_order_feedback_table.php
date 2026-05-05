<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_feedback', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('order_id'); // Using the readable order_id e.g. GFT24-1001
            $blueprint->integer('rating');
            $blueprint->text('comment')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_feedback');
    }
};
