<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для кеширования данных панели продавца
 * Ускоряет работу vendor dashboard
 */
class VendorCacheService
{
    const CACHE_TTL_SHORT = 300;   // 5 минут
    const CACHE_TTL_MEDIUM = 900;  // 15 минут
    const CACHE_TTL_LONG = 1800;   // 30 минут

    /**
     * Получить статистику продаж продавца (кеш 5 минут)
     */
    public static function getVendorSalesStats(int $vendorId): array
    {
        return Cache::remember("vendor:{$vendorId}:sales_stats", self::CACHE_TTL_SHORT, function () use ($vendorId) {
            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();

            return [
                'total_products' => Product::where('seller_id', $vendorId)
                    ->where('status', 1)
                    ->count(),

                'pending_orders' => Order::where('seller_id', $vendorId)
                    ->where('order_status', 'pending')
                    ->count(),

                'today_orders' => Order::where('seller_id', $vendorId)
                    ->whereDate('created_at', '>=', $today)
                    ->count(),

                'today_revenue' => Order::where('seller_id', $vendorId)
                    ->whereDate('created_at', '>=', $today)
                    ->where('payment_status', 'paid')
                    ->sum('order_amount'),

                'month_revenue' => Order::where('seller_id', $vendorId)
                    ->whereDate('created_at', '>=', $thisMonth)
                    ->where('payment_status', 'paid')
                    ->sum('order_amount'),

                'total_revenue' => Order::where('seller_id', $vendorId)
                    ->where('payment_status', 'paid')
                    ->sum('order_amount'),
            ];
        });
    }

    /**
     * Получить топ-продаваемые товары продавца
     */
    public static function getTopSellingProducts(int $vendorId, int $limit = 10): array
    {
        return Cache::remember("vendor:{$vendorId}:top_products:{$limit}", self::CACHE_TTL_MEDIUM, function () use ($vendorId, $limit) {
            return Product::where('seller_id', $vendorId)
                ->where('status', 1)
                ->withCount(['orderDetails as total_sold' => function ($query) {
                    $query->select(DB::raw('SUM(qty)'));
                }])
                ->orderBy('total_sold', 'desc')
                ->limit($limit)
                ->get(['id', 'name', 'thumbnail', 'unit_price'])
                ->toArray();
        });
    }

    /**
     * Получить товары с низким запасом
     */
    public static function getLowStockProducts(int $vendorId, int $threshold = 10): array
    {
        return Cache::remember("vendor:{$vendorId}:low_stock:{$threshold}", self::CACHE_TTL_MEDIUM, function () use ($vendorId, $threshold) {
            return Product::where('seller_id', $vendorId)
                ->where('status', 1)
                ->where('current_stock', '<=', $threshold)
                ->where('current_stock', '>', 0)
                ->orderBy('current_stock', 'asc')
                ->get(['id', 'name', 'current_stock', 'thumbnail'])
                ->toArray();
        });
    }

    /**
     * Получить статистику по категориям продавца
     */
    public static function getCategoryStats(int $vendorId): array
    {
        return Cache::remember("vendor:{$vendorId}:category_stats", self::CACHE_TTL_LONG, function () use ($vendorId) {
            return DB::table('products')
                ->select('category_id', DB::raw('COUNT(*) as product_count'))
                ->where('seller_id', $vendorId)
                ->where('status', 1)
                ->groupBy('category_id')
                ->orderBy('product_count', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
        });
    }

    /**
     * Получить недавние заказы продавца
     */
    public static function getRecentOrders(int $vendorId, int $limit = 20): array
    {
        return Cache::remember("vendor:{$vendorId}:recent_orders:{$limit}", self::CACHE_TTL_SHORT, function () use ($vendorId, $limit) {
            return Order::where('seller_id', $vendorId)
                ->with(['customer:id,f_name,l_name'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['id', 'order_amount', 'order_status', 'payment_status', 'customer_id', 'created_at'])
                ->toArray();
        });
    }

    /**
     * Очистить кеш конкретного продавца
     */
    public static function clearVendor(int $vendorId): void
    {
        $patterns = [
            "vendor:{$vendorId}:sales_stats",
            "vendor:{$vendorId}:top_products:*",
            "vendor:{$vendorId}:low_stock:*",
            "vendor:{$vendorId}:category_stats",
            "vendor:{$vendorId}:recent_orders:*",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Для паттернов с wildcard нужно перебрать возможные варианты
                $baseKey = str_replace('*', '', $pattern);
                for ($i = 1; $i <= 100; $i++) {
                    Cache::forget($baseKey . $i);
                }
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Очистить кеш после обновления товара
     */
    public static function clearProductCache(int $vendorId, int $productId): void
    {
        Cache::forget("vendor:{$vendorId}:sales_stats");
        Cache::forget("vendor:{$vendorId}:category_stats");

        // Очистить топ товары
        foreach ([10, 20, 50] as $limit) {
            Cache::forget("vendor:{$vendorId}:top_products:{$limit}");
        }
    }

    /**
     * Очистить кеш после нового заказа
     */
    public static function clearOrderCache(int $vendorId): void
    {
        Cache::forget("vendor:{$vendorId}:sales_stats");

        foreach ([10, 20, 50] as $limit) {
            Cache::forget("vendor:{$vendorId}:recent_orders:{$limit}");
        }
    }
}
