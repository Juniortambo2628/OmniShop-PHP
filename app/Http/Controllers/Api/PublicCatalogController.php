<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class PublicCatalogController extends Controller
{
    public function login(Request $request, $event_slug)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Password is required.'], 422);
        }

        $event = Event::where('slug', $event_slug)->first();
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        if (Hash::check($request->password, $event->password)) {
            // Generate a simple token (we use a custom one for public catalog)
            $token = base64_encode(json_encode([
                'event' => $event_slug,
                'auth' => true,
                'time' => time()
            ]));
            
            return response()->json([
                'message' => 'Login successful.',
                'token' => $token
            ]);
        }

        return response()->json(['message' => 'Invalid password.'], 401);
    }

    public function getData($event_slug)
    {
        $event = Event::where('slug', $event_slug)->first();
        
        if (!$event) {
            if ($event_slug === 'public') {
                $event = (object)[
                    'name' => 'OmniShop Public Storefront',
                    'slug' => 'public',
                    'logo' => '/static/images/logo.png',
                    'dates' => 'Year Round',
                    'venue' => 'Online',
                    'contact_email' => 'info@omnispace3d.com',
                    'deadlines' => [],
                ];
            } else {
                return response()->json(['message' => 'Event not found.'], 404);
            }
        }

        $settings = Setting::pluck('value', 'key')->toArray();
        $products = $this->getMergedProducts();

        $grouped = collect($products)->groupBy('category');
        $categories = $grouped->keys()->toArray();

        // Build image mapping from filesystem
        $images = [];
        $imagePath = public_path('static/images/products');
        if (File::isDirectory($imagePath)) {
            $files = File::files($imagePath);
            foreach ($files as $file) {
                $filename = $file->getFilename();
                // Expected format: CODE-COLOR.jpg or CODE.jpg
                if (preg_match('/^([^-.]+)(?:-([^-.]+))?\.(?:jpg|jpeg|png|webp)$/i', $filename, $matches)) {
                    $code = strtoupper($matches[1]);
                    $color = isset($matches[2]) ? $matches[2] : 'default';
                    
                    if (!isset($images[$code])) $images[$code] = [];
                    $images[$code][$color] = $filename;
                }
            }
        }

        return response()->json([
            'event' => $event,
            'products' => $products,
            'grouped' => $grouped,
            'categories' => $categories,
            'settings' => $settings,
            'images' => $images,
        ]);
    }

    public function checkout(Request $request, $event_slug)
    {
        $data = $request->validate([
            'company_name' => 'nullable|string',
            'contact_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'booth_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'total_amount' => 'required|numeric',
            'promo_code' => 'nullable|string',
            'discount_amount' => 'nullable|numeric',
            'delivery_cost' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            // Generate Order ID (e.g., GITEX-0001)
            $seq = DB::table('order_sequence')->where('event_slug', $event_slug)->value('last_number') ?? 0;
            $seq++;
            DB::table('order_sequence')->updateOrInsert(['event_slug' => $event_slug], ['last_number' => $seq]);
            
            $prefix = strtoupper($event_slug);
            $orderIdStr = $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $subtotal = collect($data['items'])->sum(fn($item) => $item['price'] * $item['quantity']);

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

            foreach ($data['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_code' => $item['product_id'],
                    'product_name' => $item['name'] ?? 'Product',
                    'color_name' => $item['color'] ?? null,
                    'category' => $item['category'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['price'] * $item['quantity'],
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
        $catalogProducts = config('catalog.products', []);
        $catalogCategories = config('catalog.categories', []);
        $adminProducts = Product::where('is_active', 1)->get();

        // Build a category map for quick lookup
        $catMap = collect($catalogCategories)->pluck('name', 'id')->toArray();

        $allProducts = [];

        // 1. Process Config Products (Flat Array)
        if (is_array($catalogProducts)) {
            foreach ($catalogProducts as $item) {
                $dbProd = Product::where('prod_id', $item['id'])->first();
                $catId = $item['category_id'] ?? 'General';
                
                $allProducts[] = [
                    'id' => $item['id'],
                    'code' => $item['id'],
                    'name' => $item['name'] ?? 'Unnamed Product',
                    'price' => $item['price'] ?? 0,
                    'image' => $item['image'] ?? null,
                    'category' => $catMap[$catId] ?? $catId,
                    'description' => $item['description'] ?? '',
                    'dimensions' => $item['dimensions'] ?? '',
                    'unit' => $item['unit'] ?? 'per event',
                    'is_poa' => $item['is_poa'] ?? false,
                    'stock_limit' => $dbProd ? $dbProd->stock_limit : null,
                    'stock_used' => $dbProd ? $dbProd->stock_used : 0,
                    'colors' => $item['colors'] ?? [],
                ];
            }
        }

        // 2. Add Admin-only products (not in config)
        $existingIds = collect($allProducts)->pluck('id')->toArray();
        foreach ($adminProducts as $prod) {
            if (in_array($prod->prod_id, $existingIds)) continue;

            $allProducts[] = [
                'id' => $prod->prod_id,
                'code' => $prod->prod_id,
                'name' => $prod->name,
                'price' => $prod->price,
                'image' => $prod->image,
                'category' => 'Custom',
                'description' => $prod->description ?? '',
                'dimensions' => $prod->dimensions ?? '',
                'unit' => 'per event',
                'is_poa' => false,
                'stock_limit' => $prod->stock_limit,
                'stock_used' => $prod->stock_used,
                'colors' => [],
            ];
        }

        return $allProducts;
    }
}
