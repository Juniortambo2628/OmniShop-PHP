<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $events = config('events');
        $filterEvent = $request->input('event') ?? (array_key_first($events) ?: '');

        $stockLevels = \App\Models\StockLimit::all()->keyBy('product_code');
        
        $stockUsage = collect();
        if ($filterEvent) {
            $stockUsage = \App\Models\OrderItem::whereHas('order', function($q) use ($filterEvent) {
                $q->where('event_slug', $filterEvent)->where('status', '!=', 'Cancelled');
            })->select('product_code', \DB::raw('SUM(quantity) as total_ordered'))
              ->groupBy('product_code')
              ->get()
              ->keyBy('product_code');
        }

        $stockData = [];
        foreach ($stockLevels as $code => $row) {
            $used = $stockUsage->has($code) ? (int)$stockUsage->get($code)->total_ordered : 0;
            $stockData[$code] = [
                'limit'     => (int)$row->stock_limit,
                'used'      => $used,
                'name'      => $row->product_name ?? $code,
                'available' => max(0, (int)$row->stock_limit - $used),
            ];
        }

        // Add products from usage that aren't in limits
        foreach ($stockUsage as $code => $usage) {
            if (!isset($stockData[$code])) {
                $stockData[$code] = [
                    'limit' => 0,
                    'used' => (int)$usage->total_ordered,
                    'available' => 0,
                    'name' => $code,
                ];
            }
        }

        return view('admin.stock.index', compact('events', 'filterEvent', 'stockData'));
    }

    public function update(Request $request)
    {
        $limits = $request->input('limits', []);
        $event_slug = $request->input('event_slug');

        foreach ($limits as $code => $limit) {
            $code = strtoupper(trim($code));
            if ($code === '' || $code === 'NEW_CODE') continue;

            \App\Models\StockLimit::updateOrCreate(
                ['product_code' => $code],
                [
                    'stock_limit' => (int)$limit,
                    'product_name' => $code, // Ideally we'd get the name from catalog if possible
                ]
            );
        }

        return back()->with('success', 'Stock limits saved.');
    }
}
