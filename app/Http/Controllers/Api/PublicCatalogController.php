<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\PromoCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class PublicCatalogController extends Controller
{
    public function login(Request $request, $event_slug)
    {
        $event = config("events.$event_slug");
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        $password = $request->input('password');
        
        $correct = Setting::where('key', "catalog_password_{$event_slug}")->value('value') 
                   ?? $event['catalog_password_default'];
        $demo = Setting::where('key', "catalog_demo_password_{$event_slug}")->value('value');

        if ($password === $correct || ($demo && $password === $demo)) {
            // In a real scenario, use a signed token or Sanctum. 
            // For simplicity in this migration, we'll return a simple auth token flag.
            $token = base64_encode(json_encode(['event' => $event_slug, 'auth' => true, 'time' => time()]));
            return response()->json(['token' => $token]);
        }

        return response()->json(['message' => 'Incorrect password.'], 401);
    }

    public function data(Request $request, $event_slug)
    {
        if ($event_slug === 'public') {
            $event = ['name' => 'Storefront Catalog', 'short_name' => 'Catalog'];
        } else {
            $event = config("events.$event_slug");
            if (!$event) {
                return response()->json(['message' => 'Event not found.'], 404);
            }
        }

        $categories = collect(config('catalog.categories'))->pluck('name', 'id')->toArray();
        $products = $this->getMergedProducts();
        $images = $this->getProductImages();

        // Group products by category
        $grouped = [];
        foreach ($products as $p) {
            $grouped[$p['category_id']][] = $p;
        }

        return response()->json([
            'event' => $event,
            'categories' => $categories,
            'grouped' => $grouped,
            'images' => $images,
        ]);
    }

    public function checkout(Request $request, $event_slug)
    {
        $event = config("events.$event_slug");
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        $data = $request->validate([
            'company_name' => 'nullable|string',
            'contact_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'booth_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'promo_code' => 'nullable|string',
            'discount_amount' => 'nullable|numeric',
            'delivery_cost' => 'nullable|numeric',
            'total_amount' => 'required|numeric',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.color' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate unique Order ID
            $seq = DB::table('order_sequence')->where('event_slug', $event_slug)->value('last_number') ?? 0;
            $seq++;
            DB::table('order_sequence')->updateOrInsert(['event_slug' => $event_slug], ['last_number' => $seq]);
            $orderIdStr = $event_slug . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            $order = Order::create([
                'order_id' => $orderIdStr,
                'event_slug' => $event_slug,
                'company_name' => $data['company_name'] ?? '',
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'booth_number' => $data['booth_number'] ?? '',
                'notes' => $data['notes'] ?? '',
                'promo_code' => $data['promo_code'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'delivery_cost' => $data['delivery_cost'] ?? 0,
                'subtotal' => $subtotal,
                'total' => $data['total_amount'],
                'status' => 'Pending',
            ]);

            // Increment promo usage if provided
            if (!empty($data['promo_code'])) {
                $promo = PromoCode::where('code', $data['promo_code'])->first();
                if ($promo) {
                    $promo->increment('times_used');
                }
            }

            foreach ($data['items'] as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_code' => $item['product_id'],
                    'product_name' => $item['name'],
                    'unit_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'color_name' => $item['color'] ?? null,
                    'total_price' => $subtotal,
                ]);

                // Update stock if tracked
                $product = Product::where('prod_id', $item['product_id'])->first();
                if ($product && $product->stock_limit !== null) {
                    $product->stock_used += $item['quantity'];
                    $product->save();
                }
            }

            DB::commit();

            // Create notification for admin
            \App\Services\NotificationService::notifyNewOrder($order);

            // Check if emails are enabled and send confirmation
            $settings = Setting::pluck('value', 'key')->toArray();
            if (($settings['enable_order_emails'] ?? '0') === '1') {
                try {
                    \Illuminate\Support\Facades\Mail::to($order->email)
                        ->send(new \App\Mail\OrderConfirmationMail($order));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Auto-email failed: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'Order submitted successfully',
                'order_id' => $order->order_id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to submit order.', 'error' => $e->getMessage()], 500);
        }
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
            
            $basename = $file->getFilenameWithoutExtension();
            $stem = strtoupper($basename);
            
            if (preg_match('/^(.+)-(\d{1,2})$/', $basename, $m)) {
                $code = strtoupper($m[1]);
                $colorId = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $images[$code][$colorId] = $fname;
            } else {
                $images[$stem]['default'] = $fname;
            }
        }
        return $images;
    }
}
