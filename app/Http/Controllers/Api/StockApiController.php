<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class StockApiController extends Controller
{
    public function index(Request $request)
    {
        $catalogProducts = config('catalog.products', []);
        $dbProducts = Product::all()->keyBy('prod_id');

        $filterQ = $request->input('q');
        $stockData = [];
        
        foreach ($catalogProducts as $cp) {
            $prodId = $cp['id'];
            
            if ($filterQ) {
                $q = strtolower($filterQ);
                if (!str_contains(strtolower($cp['code']), $q) && !str_contains(strtolower($cp['name']), $q)) {
                    continue;
                }
            }

            $dbProd = $dbProducts->get($prodId);
            $stockData[] = [
                'prod_id'      => $prodId,
                'code'         => $cp['code'],
                'name'         => $cp['name'],
                'category_id'  => $cp['category_id'],
                'stock_limit'  => $dbProd ? $dbProd->stock_limit : null,
                'stock_used'   => $dbProd ? $dbProd->stock_used : 0,
            ];
        }

        $perPage = (int)$request->input('per_page', 20);
        $page = (int)$request->input('page', 1);
        $total = count($stockData);
        $pagedData = array_slice($stockData, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'data'         => $pagedData,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => ceil($total / $perPage),
            'per_page'     => $perPage,
        ]);
    }

    public function update(Request $request, $productId)
    {
        $request->validate(['stock_limit' => 'nullable|integer|min:0']);

        // Find in catalog to get correct defaults
        $catalog = collect(config('catalog.products'))->firstWhere('id', $productId);

        $product = Product::updateOrCreate(
            ['prod_id' => $productId],
            [
                'code'         => $catalog['code'] ?? $productId,
                'name'         => $catalog['name'] ?? $productId,
                'category_id'  => $catalog['category_id'] ?? '',
                'price'        => $catalog['price'] ?? 0,
                'price_display'=> $catalog['price_display'] ?? '$0',
                'is_override'  => true,
                'stock_limit'  => $request->input('stock_limit'),
            ]
        );

        if ($request->has('stock_limit')) {
            $product->stock_limit = $request->input('stock_limit');
            $product->save();
        }

        return response()->json(['message' => 'Stock updated.', 'product' => $product]);
    }

    public function bulkReset(Request $request)
    {
        $ids = $request->input('ids', []);
        Product::whereIn('prod_id', $ids)->update(['stock_used' => 0]);
        return response()->json(['message' => count($ids) . ' products stock reset']);
    }
}
