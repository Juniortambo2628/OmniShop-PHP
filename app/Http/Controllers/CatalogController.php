<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\File;

class CatalogController extends Controller
{
    public function index($event_slug)
    {
        $event = config("events.$event_slug");
        if (!$event) {
            abort(404);
        }

        $categories = config('catalog.categories');
        $products = $this->getMergedProducts();
        $images = $this->getProductImages();

        // Group products by category
        $grouped = [];
        foreach ($products as $p) {
            $grouped[$p['category_id']][] = $p;
        }

        return view('catalog.index', compact('event', 'categories', 'grouped', 'images'));
    }

    public function checkout($event_slug)
    {
        $event = config("events.$event_slug");
        if (!$event) abort(404);
        return view('catalog.checkout', compact('event'));
    }

    public function confirmation($event_slug, $order_id)
    {
        $event = config("events.$event_slug");
        $order = \App\Models\Order::with('items')->where('order_id', $order_id)->firstOrFail();
        
        return view('catalog.confirmation', compact('event', 'order'));
    }

    public function loginForm($event_slug)
    {
        $event = config("events.$event_slug");
        if (!$event) abort(404);

        if (session("catalog_auth_{$event_slug}")) {
            return redirect()->route('catalog', $event_slug);
        }

        return view('catalog.login', compact('event'));
    }

    public function login(Request $request, $event_slug)
    {
        $event = config("events.$event_slug");
        if (!$event) abort(404);

        $password = $request->input('password');
        
        $correct = \App\Models\Setting::where('key', "catalog_password_{$event_slug}")->value('value') 
                   ?? $event['catalog_password_default'];
        $demo = \App\Models\Setting::where('key', "catalog_demo_password_{$event_slug}")->value('value');

        if ($password === $correct || ($demo && $password === $demo)) {
            session(["catalog_auth_{$event_slug}" => true]);
            return redirect()->route('catalog', $event_slug);
        }

        return back()->with('error', 'Incorrect password. Please try again.');
    }

    private function getMergedProducts()
    {
        $catalogProducts = config('catalog.products');
        $adminProducts = Product::where('is_active', 1)->get()->toArray();

        $catIndex = [];
        foreach ($catalogProducts as $i => $p) {
            $catIndex[$p['id']] = $i;
        }

        foreach ($adminProducts as $ap) {
            if (!empty($ap['is_override']) && isset($ap['prod_id']) && isset($catIndex[$ap['prod_id']])) {
                $idx = $catIndex[$ap['prod_id']];
                $catalogProducts[$idx] = array_merge($catalogProducts[$idx], $ap);
            } else {
                $catalogProducts[] = $ap;
            }
        }

        return $catalogProducts;
    }

    private function getProductImages()
    {
        $imgDir = public_path('static/images/products');
        $images = [];
        if (!File::isDirectory($imgDir)) return $images;

        $files = File::files($imgDir);
        foreach ($files as $file) {
            $fname = $file->getFilename();
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
            
            $stem = strtoupper($file->getFilenameWithoutExtension());
            if (preg_match('/^(.+)-(\d{1,2})$/', $stem, $m)) {
                $code = $m[1];
                $colorId = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $images[$code][$colorId] = $fname;
            } else {
                $images[$stem]['default'] = $fname;
            }
        }
        return $images;
    }
}
