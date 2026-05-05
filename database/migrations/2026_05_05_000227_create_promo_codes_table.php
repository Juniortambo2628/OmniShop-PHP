<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('code')->unique();
            $blueprint->enum('type', ['fixed', 'percentage'])->default('fixed');
            $blueprint->decimal('value', 10, 2);
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamp('expires_at')->nullable();
            $blueprint->integer('usage_limit')->nullable();
            $blueprint->integer('times_used')->default(0);
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
