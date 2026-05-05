<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = config('catalog.products');

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['prod_id' => $p['id']],
                [
                    'code'          => $p['code'],
                    'name'          => $p['name'],
                    'category_id'   => $p['category_id'],
                    'price'         => $p['price'],
                    'price_display' => $p['price_display'],
                    'dimensions'    => $p['dimensions'] ?? '',
                    'colors_json'   => $p['colors'] ?? [],
                    'is_poa'        => $p['is_poa'] ?? false,
                    'is_active'     => true,
                    'is_override'   => 1, // Treat everything as managed from DB once seeded
                ]
            );
        }
    }
}
