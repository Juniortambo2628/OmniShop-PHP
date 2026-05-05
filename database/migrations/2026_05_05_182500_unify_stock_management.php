<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'stock_limit')) {
                $table->integer('stock_limit')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('products', 'stock_used')) {
                $table->integer('stock_used')->default(0)->after('stock_limit');
            }
        });

        // Drop redundant table if it exists
        Schema::dropIfExists('stock_limits');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_limit', 'stock_used']);
        });
    }
};
