<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.dashboard');
    }

    public function show($id)
    {
        $order = \App\Models\Order::with('items')->where('order_id', $id)->firstOrFail();
        $event = config("events.{$order->event_slug}");
        $statuses = ['Pending', 'Approved', 'Invoiced', 'Fulfilled', 'Cancelled'];
        
        return view('admin.orders.show', compact('order', 'event', 'statuses'));
    }

    public function update(Request $request, $id)
    {
        $order = \App\Models\Order::where('order_id', $id)->firstOrFail();
        
        if ($request->input('action') === 'update_status') {
            $order->status = $request->input('status');
            $order->save();
            return back()->with('success', 'Status updated successfully.');
        }

        return back();
    }
}
