<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProductApiController extends Controller
{
    public function index(Request $request)
    {
        $filterCat = $request->input('cat');
        $filterQ = $request->input('q');

        $catalogProducts = config('catalog.products');
        $dbProducts = Product::all();

        $dbByProdId = $dbProducts->where('is_override', 1)->keyBy('prod_id');
        $dbAdditions = $dbProducts->where('is_override', 0);

        $displayProducts = [];
        
        // Phase 1: Build light-weight list for filtering
        foreach ($catalogProducts as $cp) {
            $override = $dbByProdId->get($cp['id']);
            $code = $override->code ?? $cp['code'] ?? '';
            
            $displayProducts[] = [
                'source'       => $override ? 'modified' : 'builtin',
                'db_id'        => $override ? $override->id : null,
                'code'         => $code,
                'name'         => $override->name        ?? $cp['name'] ?? '',
                'category_id'  => $override->category_id ?? $cp['category_id'] ?? '',
                'price'        => $override->price       ?? $cp['price'] ?? 0,
                'price_display'=> $override->price_display ?? $cp['price_display'] ?? '',
                'is_poa'       => $override ? $override->is_poa : ($cp['is_poa'] ?? false),
                'is_active'    => $override ? $override->is_active : true,
                'dimensions'   => $override->dimensions  ?? $cp['dimensions'] ?? '',
                'colors'       => $override ? $override->colors : ($cp['colors'] ?? []),
                'catalog_id'   => $cp['id'],
            ];
        }

        foreach ($dbAdditions as $dp) {
            $displayProducts[] = [
                'source'       => 'custom',
                'db_id'        => $dp->id,
                'code'         => $dp->code,
                'name'         => $dp->name,
                'category_id'  => $dp->category_id,
                'price'        => $dp->price,
                'price_display'=> $dp->price_display,
                'is_poa'       => $dp->is_poa,
                'is_active'    => $dp->is_active,
                'dimensions'   => $dp->dimensions,
                'colors'       => $dp->colors,
                'catalog_id'   => null,
            ];
        }

        // Apply filters
        if ($filterCat) {
            $displayProducts = array_values(array_filter($displayProducts, fn($p) => $p['category_id'] === $filterCat));
        }
        if ($filterQ) {
            $q = strtolower($filterQ);
            $displayProducts = array_values(array_filter($displayProducts, fn($p) =>
                str_contains(strtolower($p['code']), $q) || str_contains(strtolower($p['name'] ?? ''), $q)
            ));
        }

        // Pagination
        $perPage = (int)$request->input('per_page', 24);
        $page = (int)$request->input('page', 1);
        $total = count($displayProducts);
        $pagedProducts = array_slice($displayProducts, ($page - 1) * $perPage, $perPage);

        // Phase 2: Add expensive data (images) ONLY for the paged slice
        $imagePath = public_path('static/images/products');
        foreach ($pagedProducts as &$p) {
            $img = 'placeholder.jpg';
            if ($p['code']) {
                $files = File::glob($imagePath . '/' . strtoupper($p['code']) . '*.jpg');
                if ($files && count($files) > 0) {
                    $img = basename($files[0]);
                } else {
                    $files = File::glob($imagePath . '/' . strtolower($p['code']) . '*.jpg');
                    if ($files && count($files) > 0) {
                        $img = basename($files[0]);
                    }
                }
            }
            $p['image'] = $img;
        }

        $categories = collect(config('catalog.categories'))->pluck('name', 'id')->toArray();

        return response()->json([
            'data'         => $pagedProducts,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => ceil($total / $perPage),
            'per_page'     => $perPage,
            'categories'   => $categories,
        ]);
    }

    public function showCatalog($catalogId)
    {
        $catalogProducts = config('catalog.products');
        $cp = collect($catalogProducts)->firstWhere('id', $catalogId);
        
        if (!$cp) {
            return response()->json(['error' => 'Catalog item not found'], 404);
        }

        // Check for override
        $override = Product::where('prod_id', $catalogId)->where('is_override', 1)->first();

        $code = $override->code ?? $cp['code'] ?? '';
        
        // Find image
        $img = 'placeholder.jpg';
        $imagePath = public_path('static/images/products');
        if ($code) {
            $files = File::glob($imagePath . '/' . strtoupper($code) . '*.jpg');
            if ($files && count($files) > 0) {
                $img = basename($files[0]);
            }
        }

        return response()->json([
            'source'       => $override ? 'modified' : 'builtin',
            'db_id'        => $override ? $override->id : null,
            'code'         => $code,
            'name'         => $override->name        ?? $cp['name'] ?? '',
            'category_id'  => $override->category_id ?? $cp['category_id'] ?? '',
            'price'        => $override->price       ?? $cp['price'] ?? 0,
            'price_display'=> $override->price_display ?? $cp['price_display'] ?? '',
            'is_poa'       => $override ? $override->is_poa : ($cp['is_poa'] ?? false),
            'is_active'    => $override ? $override->is_active : true,
            'dimensions'   => $override->dimensions  ?? $cp['dimensions'] ?? '',
            'colors'       => $override ? $override->colors : ($cp['colors'] ?? []),
            'catalog_id'   => $cp['id'],
            'image'        => $img,
        ]);
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prod_id'       => 'required|string',
            'code'          => 'required|string',
            'name'          => 'required|string',
            'category_id'   => 'required|string',
            'price'         => 'required|numeric',
            'price_display' => 'required|string',
            'dimensions'    => 'nullable|string',
            'colors_json'   => 'nullable|array',
            'is_poa'        => 'boolean',
            'is_active'     => 'boolean',
            'is_override'   => 'boolean',
        ]);

        $product = Product::create($data);

        return response()->json(['message' => 'Product created.', 'product' => $product], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'code'          => 'required|string',
            'name'          => 'required|string',
            'category_id'   => 'required|string',
            'price'         => 'required|numeric',
            'price_display' => 'required|string',
            'dimensions'    => 'nullable|string',
            'colors_json'   => 'nullable|array',
            'is_poa'        => 'boolean',
            'is_active'     => 'boolean',
        ]);

        $product->update($data);

        return response()->json(['message' => 'Product updated.', 'product' => $product]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        // Only delete products that have a db_id (custom or overrides)
        Product::whereIn('id', $ids)->delete();
        return response()->json(['message' => count($ids) . ' products deleted from overrides/custom list']);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $ids = $request->input('ids', []);
        $isActive = (bool)$request->input('is_active');
        Product::whereIn('id', $ids)->update(['is_active' => $isActive]);
        return response()->json(['message' => count($ids) . ' products updated']);
    }
}
