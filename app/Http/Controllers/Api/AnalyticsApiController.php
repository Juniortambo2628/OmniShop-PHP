<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsApiController extends Controller
{
    public function index()
    {
        // 1. Revenue Over Time (Last 30 Days)
        $revenueOverTime = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 2. Order Status Distribution
        $statusDistribution = DB::table('orders')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // 3. Top Products
        $topProducts = DB::table('order_items')
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('product_name')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();

        // 4. Event Performance
        $eventPerformance = DB::table('orders')
            ->select('event_slug', DB::raw('count(*) as order_count'), DB::raw('SUM(total) as total_revenue'))
            ->groupBy('event_slug')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // 5. Generate Insights (Calculated observations)
        $insights = $this->generateInsights($revenueOverTime, $topProducts, $eventPerformance);

        return response()->json([
            'revenue_over_time' => $revenueOverTime,
            'status_distribution' => $statusDistribution,
            'top_products' => $topProducts,
            'event_performance' => $eventPerformance,
            'insights' => $insights
        ]);
    }

    private function generateInsights($revenue, $products, $events)
    {
        $insights = [];

        // High Revenue Insight
        if ($revenue->count() > 1) {
            $last = $revenue->last()->total;
            $prev = $revenue->get($revenue->count() - 2)->total;
            $diff = $last - $prev;
            if ($diff > 0) {
                $insights[] = [
                    'type' => 'positive',
                    'title' => 'Revenue is trending up',
                    'text' => "Daily revenue increased by $" . number_format($diff, 2) . " compared to yesterday."
                ];
            }
        }

        // Top Product Insight
        if ($products->count() > 0) {
            $top = $products->first();
            $insights[] = [
                'type' => 'info',
                'title' => 'Best Seller identified',
                'text' => "{$top->product_name} is currently your top performer, generating $" . number_format($top->total_revenue, 2) . " in revenue."
            ];
        }

        // Event Insight
        if ($events->count() > 0) {
            $topEvent = $events->first();
            $insights[] = [
                'type' => 'event',
                'title' => 'Top Event Performance',
                'text' => "The event '{$topEvent->event_slug}' is driving the most traffic with {$topEvent->order_count} orders."
            ];
        }

        return $insights;
    }
}
