<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filterCat = $request->input('cat');
        $filterQ = $request->input('q');

        $catalogProducts = config('catalog.products');
        $dbProducts = \App\Models\Product::all();

        $dbByProdId = $dbProducts->where('is_override', 1)->keyBy('prod_id');
        $dbAdditions = $dbProducts->where('is_override', 0);

        $displayProducts = [];
        foreach ($catalogProducts as $cp) {
            $override = $dbByProdId->get($cp['id']);
            $displayProducts[] = [
                'source'      => $override ? 'modified' : 'builtin',
                'db_id'       => $override ? $override->id : null,
                'code'        => $override->code        ?? $cp['code'] ?? '',
                'name'        => $override->name        ?? $cp['name'] ?? '',
                'category_id' => $override->category_id ?? $cp['category_id'] ?? '',
                'price'       => $override->price       ?? $cp['price'] ?? 0,
                'price_display'=> $override->price_display ?? $cp['price_display'] ?? '',
                'is_poa'      => $override ? $override->is_poa : ($cp['is_poa'] ?? false),
                'is_active'   => $override ? $override->is_active : true,
                'dimensions'  => $override->dimensions  ?? $cp['dimensions'] ?? '',
                'colors'      => $override ? $override->colors : ($cp['colors'] ?? []),
                'catalog_id'  => $cp['id'],
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

        if ($filterCat) {
            $displayProducts = array_filter($displayProducts, fn($p) => $p['category_id'] === $filterCat);
        }
        if ($filterQ) {
            $q = strtolower($filterQ);
            $displayProducts = array_filter($displayProducts, fn($p) => str_contains(strtolower($p['code']), $q) || str_contains(strtolower($p['name'] ?? ''), $q));
        }

        $categoryLabels = collect(config('catalog.categories'))->pluck('name', 'id')->toArray();
        
        return view('admin.products.index', compact('displayProducts', 'categoryLabels', 'filterCat', 'filterQ'));
    }

    public function create(Request $request)
    {
        $categories = config('catalog.categories');
        $product = new \App\Models\Product();
        
        // Pre-fill from catalog if requested
        $fromCatalog = $request->input('from_catalog');
        if ($fromCatalog) {
            $catalogProducts = config('catalog.products');
            $cp = collect($catalogProducts)->firstWhere('id', $fromCatalog);
            if ($cp) {
                $product->fill([
                    'prod_id' => $cp['id'],
                    'code' => $cp['code'] ?? '',
                    'name' => $cp['name'] ?? '',
                    'category_id' => $cp['category_id'] ?? '',
                    'price' => $cp['price'] ?? 0,
                    'price_display' => $cp['price_display'] ?? '',
                    'is_poa' => $cp['is_poa'] ?? false,
                    'is_active' => true,
                    'dimensions' => $cp['dimensions'] ?? '',
                    'is_override' => true
                ]);
                $product->colors_json = $cp['colors'] ?? [];
            }
        }

        return view('admin.products.form', compact('product', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prod_id' => 'required|string',
            'code' => 'required|string',
            'name' => 'required|string',
            'category_id' => 'required|string',
            'price' => 'required|numeric',
            'price_display' => 'required|string',
            'dimensions' => 'nullable|string',
            'colors' => 'nullable|string',
            'is_poa' => 'boolean',
            'is_active' => 'boolean',
            'is_override' => 'boolean',
        ]);

        $colors = $this->parseColors($request->input('colors'));
        $data['colors_json'] = $colors;

        \App\Models\Product::create($data);

        return redirect()->route('admin.products')->with('success', 'Product created successfully.');
    }

    public function edit($id)
    {
        $categories = config('catalog.categories');
        $product = \App\Models\Product::findOrFail($id);
        
        return view('admin.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = \App\Models\Product::findOrFail($id);

        $data = $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'category_id' => 'required|string',
            'price' => 'required|numeric',
            'price_display' => 'required|string',
            'dimensions' => 'nullable|string',
            'colors' => 'nullable|string',
            'is_poa' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $colors = $this->parseColors($request->input('colors'));
        $data['colors_json'] = $colors;

        $product->update($data);

        return redirect()->route('admin.products')->with('success', 'Product updated successfully.');
    }

    private function parseColors($colorsString)
    {
        if (empty($colorsString)) return [];
        
        $parts = array_map('trim', explode(',', $colorsString));
        $colors = [];
        foreach ($parts as $index => $name) {
            if ($name !== '') {
                $colors[] = [
                    'id' => str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                    'name' => $name
                ];
            }
        }
        return $colors;
    }
}
