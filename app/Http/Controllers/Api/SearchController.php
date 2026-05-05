<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->input('q', '');

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // Search orders
        $orders = Order::where('order_id', 'LIKE', "%{$q}%")
            ->orWhere('company_name', 'LIKE', "%{$q}%")
            ->orWhere('contact_name', 'LIKE', "%{$q}%")
            ->orWhere('email', 'LIKE', "%{$q}%")
            ->limit(5)
            ->get();

        foreach ($orders as $order) {
            $results[] = [
                'type'  => 'order',
                'id'    => $order->order_id,
                'title' => "Order {$order->order_id}",
                'subtitle' => "{$order->company_name} — {$order->contact_name}",
                'url'   => "/admin/orders/{$order->order_id}",
            ];
        }

        // Search products from config
        $catalogProducts = config('catalog.products');
        $lq = strtolower($q);
        $matchCount = 0;

        foreach ($catalogProducts as $cp) {
            if ($matchCount >= 5) break;
            if (str_contains(strtolower($cp['code']), $lq) || str_contains(strtolower($cp['name']), $lq)) {
                $results[] = [
                    'type'     => 'product',
                    'id'       => $cp['id'],
                    'title'    => $cp['name'],
                    'subtitle' => "{$cp['code']} — {$cp['price_display']}",
                    'url'      => "/admin/products",
                ];
                $matchCount++;
            }
        }

        return response()->json(['results' => $results]);
    }
}
