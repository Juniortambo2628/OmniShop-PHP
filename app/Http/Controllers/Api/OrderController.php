<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function submit(Request $request)
    {
        $data = $request->validate([
            'event_slug' => 'required',
            'contact_name' => 'required',
            'company_name' => 'required',
            'booth_number' => 'required',
            'email' => 'required|email',
            'phone' => 'nullable',
            'notes' => 'nullable',
            'subtotal' => 'required|numeric',
            'vat' => 'required|numeric',
            'total' => 'required|numeric',
            'items' => 'required|array',
        ]);

        $orderId = $this->generateOrderId($data['company_name']);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'order_id' => $orderId,
                'event_slug' => $data['event_slug'],
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'booth_number' => $data['booth_number'],
                'subtotal' => $data['subtotal'],
                'vat' => $data['vat'],
                'total' => $data['total'],
                'status' => 'Pending',
                'notes' => $data['notes'] ?? '',
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_code' => $item['product_code'],
                    'product_name' => $item['product_name'],
                    'color_name' => $item['color_name'] ?? '',
                    'category' => $item['category'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['unit_price'] * $item['quantity'],
                    'dimensions' => $item['dimensions'] ?? '',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'order_id' => $orderId]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function generateOrderId($companyName)
    {
        $prefix = preg_replace('/[^A-Za-z]/', '', $companyName);
        $prefix = strtoupper(substr($prefix, 0, 3)) ?: 'UNK';
        
        $seq = DB::table('order_sequence')->insertGetId(['stub' => 1]);
        
        return sprintf('OMN-OS2026-%s-%03d', $prefix, $seq);
    }
}
