<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $filterEvent = $request->input('event');
        $filterStatus = $request->input('status');

        $query = Order::query();

        if ($filterEvent) {
            $query->where('event_slug', $filterEvent);
        }

        if ($filterStatus) {
            $query->where('status', $filterStatus);
        }

        // Compute stats via aggregate queries instead of loading everything
        $stats = [
            'total_orders'   => (clone $query)->count(),
            'total_revenue'  => (clone $query)->sum('total'),
            'pending_orders' => (clone $query)->where('status', 'Pending')->count(),
            'paid_orders'    => (clone $query)->whereIn('status', ['Approved', 'Invoiced', 'Fulfilled'])->count(),
        ];

        // Only load 10 most recent orders for the overview table
        $orders = (clone $query)->orderByDesc('created_at')->limit(10)->get();

        $events = collect(config('events', []))->map(fn($e, $slug) => [
            'slug' => $slug,
            'short_name' => $e['short_name'] ?? $slug,
        ])->values();

        $statuses = ['Pending', 'Approved', 'Invoiced', 'Fulfilled', 'Cancelled'];

        return response()->json([
            'stats'    => $stats,
            'orders'   => $orders,
            'events'   => $events,
            'statuses' => $statuses,
            'filters'  => [
                'event'  => $filterEvent,
                'status' => $filterStatus,
            ],
        ]);
    }
}

