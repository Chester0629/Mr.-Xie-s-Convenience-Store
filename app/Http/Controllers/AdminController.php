<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function stats(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Build cache key based on date range
        $cacheKey = 'admin_stats';
        if ($startDate && $endDate) {
            $cacheKey .= "_{$startDate}_{$endDate}";
        }

        return Cache::remember($cacheKey, 60, function () use ($startDate, $endDate) {
            // Base query for orders with date filter
            $ordersQuery = Order::whereIn('status', ['processing', 'shipped', 'completed']);

            if ($startDate && $endDate) {
                $ordersQuery->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            }

            // 1. Total Consumption (Actual Revenue)
            $totalSales = (clone $ordersQuery)->sum('total_amount');

            // 2. Counts
            $orderCount = (clone $ordersQuery)->count();
            $userCount = User::where('role', 'customer')->count();

            // 3. Low Stock
            $lowStockProducts = Product::where('stock', '<', 10)->take(10)->get();

            // 4. Recent Orders (always show recent, not filtered)
            $recentOrders = Order::with('user')->latest()->take(5)->get();

            // 5. Sales by Category (with date filter)
            $salesByCategoryQuery = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->whereIn('orders.status', ['processing', 'shipped', 'completed']);

            if ($startDate && $endDate) {
                $salesByCategoryQuery->whereBetween('orders.created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            }

            $salesByCategory = $salesByCategoryQuery
                ->select('categories.name as category', DB::raw('SUM(order_items.quantity * order_items.price) as total'))
                ->groupBy('categories.id', 'categories.name')
                ->get();

            // 6. AOV
            $aov = $orderCount > 0 ? round($totalSales / $orderCount) : 0;

            // 7. Top Products (with date filter)
            $topProductsQuery = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('orders.status', ['processing', 'shipped', 'completed']);

            if ($startDate && $endDate) {
                $topProductsQuery->whereBetween('orders.created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            }

            $topProducts = $topProductsQuery
                ->select('products.name', 'products.image', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('SUM(order_items.quantity * order_items.price) as total_amount'))
                ->groupBy('products.id', 'products.name', 'products.image')
                ->orderByDesc('total_qty')
                ->take(5)
                ->get();

            // 8. Chart Data - dynamic based on date range
            $chartData = [
                'labels' => [],
                'values' => []
            ];

            // Calculate chart data based on date range
            if ($startDate && $endDate) {
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
                $daysDiff = $start->diffInDays($end);

                $chartStats = Order::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->whereIn('status', ['processing', 'shipped', 'completed'])
                    ->groupBy('date')
                    ->get()
                    ->keyBy('date');

                for ($i = 0; $i <= $daysDiff; $i++) {
                    $date = $start->copy()->addDays($i)->format('Y-m-d');
                    $chartData['labels'][] = $start->copy()->addDays($i)->format('m/d');
                    $val = isset($chartStats[$date]) ? $chartStats[$date]->total : 0;
                    $chartData['values'][] = $val;
                }
            } else {
                // Default: Last 7 days
                $sevenDaysStats = Order::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
                    ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                    ->whereIn('status', ['processing', 'shipped', 'completed'])
                    ->groupBy('date')
                    ->get()
                    ->keyBy('date');

                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $chartData['labels'][] = now()->subDays($i)->format('m/d');
                    $val = isset($sevenDaysStats[$date]) ? $sevenDaysStats[$date]->total : 0;
                    $chartData['values'][] = $val;
                }
            }

            return [
                'total_sales' => $totalSales,
                'order_count' => $orderCount,
                'user_count' => $userCount,
                'low_stock_products' => $lowStockProducts,
                'recent_orders' => $recentOrders,
                'sales_by_category' => $salesByCategory,
                'aov' => $aov,
                'top_products' => $topProducts,
                'chart_data' => $chartData
            ];
        });
    }

    /**
     * Get detailed inventory report with stock alerts.
     */
    public function inventoryReport(Request $request)
    {
        $threshold = $request->get('threshold', Product::LOW_STOCK_THRESHOLD);

        // Out of stock products
        $outOfStock = Product::outOfStock()
            ->with('category')
            ->orderBy('name')
            ->get();

        // Low stock products (above 0 but below threshold)
        $lowStock = Product::lowStock($threshold)
            ->with('category')
            ->orderBy('stock', 'asc')
            ->get();

        // Summary statistics
        $totalProducts = Product::count();
        $outOfStockCount = $outOfStock->count();
        $lowStockCount = $lowStock->count();
        $healthyStockCount = $totalProducts - $outOfStockCount - $lowStockCount;

        return response()->json([
            'summary' => [
                'total_products' => $totalProducts,
                'out_of_stock' => $outOfStockCount,
                'low_stock' => $lowStockCount,
                'healthy_stock' => $healthyStockCount,
                'threshold' => $threshold,
            ],
            'out_of_stock_products' => $outOfStock,
            'low_stock_products' => $lowStock,
        ]);
    }
}
