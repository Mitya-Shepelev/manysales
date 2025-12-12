<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для кеширования каталога товаров
 * Оптимизирует частые запросы к БД через Redis
 */
class CatalogCacheService
{
    // Время жизни кеша (в секундах)
    const CACHE_TTL_SHORT = 300;      // 5 минут - для динамичных данных
    const CACHE_TTL_MEDIUM = 1800;    // 30 минут - для средней динамики
    const CACHE_TTL_LONG = 3600;      // 1 час - для стабильных данных
    const CACHE_TTL_EXTRA_LONG = 86400; // 24 часа - для статичных данных

    /**
     * Получить дерево категорий (с кешированием)
     */
    public static function getCategoriesTree(): array
    {
        return Cache::remember('catalog:categories_tree', self::CACHE_TTL_LONG, function () {
            return Category::with(['childes.childes', 'translations'])
                ->where('position', 0)
                ->orderBy('priority', 'desc')
                ->get()
                ->toArray();
        });
    }

    /**
     * Получить все категории первого уровня
     */
    public static function getParentCategories()
    {
        return Cache::remember('catalog:parent_categories', self::CACHE_TTL_LONG, function () {
            return Category::with(['childes', 'translations'])
                ->where('position', 0)
                ->where('home_status', 1)
                ->orderBy('priority', 'desc')
                ->get();
        });
    }

    /**
     * Получить категорию по slug (с кешированием)
     */
    public static function getCategoryBySlug(string $slug)
    {
        return Cache::remember("catalog:category_slug:{$slug}", self::CACHE_TTL_LONG, function () use ($slug) {
            return Category::where('slug', $slug)->with(['translations', 'seo'])->first();
        });
    }

    /**
     * Получить все бренды (с кешированием)
     */
    public static function getAllBrands()
    {
        return Cache::remember('catalog:brands_all', self::CACHE_TTL_LONG, function () {
            return Brand::where('status', 1)
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'slug', 'image', 'image_storage_type']);
        });
    }

    /**
     * Получить бренд по slug
     */
    public static function getBrandBySlug(string $slug)
    {
        return Cache::remember("catalog:brand_slug:{$slug}", self::CACHE_TTL_LONG, function () use ($slug) {
            return Brand::where('slug', $slug)
                ->where('status', 1)
                ->with(['seo'])
                ->first();
        });
    }

    /**
     * Получить диапазоны цен для фильтров
     */
    public static function getPriceRanges(?int $categoryId = null): array
    {
        $cacheKey = $categoryId
            ? "catalog:price_ranges_cat:{$categoryId}"
            : 'catalog:price_ranges_all';

        return Cache::remember($cacheKey, self::CACHE_TTL_MEDIUM, function () use ($categoryId) {
            $query = Product::where('status', 1);

            if ($categoryId) {
                $query->where('category_ids', 'like', '%"' . $categoryId . '"%');
            }

            $minPrice = $query->min('unit_price') ?? 0;
            $maxPrice = $query->max('unit_price') ?? 100000;

            return [
                'min' => floor($minPrice),
                'max' => ceil($maxPrice),
            ];
        });
    }

    /**
     * Получить популярные товары (кеш на 5 минут)
     */
    public static function getTrendingProducts(int $limit = 10): array
    {
        return Cache::remember("catalog:trending_products:{$limit}", self::CACHE_TTL_SHORT, function () use ($limit) {
            return Product::with(['rating', 'seller.shop', 'translations'])
                ->where('status', 1)
                ->withCount('orderDetails')
                ->orderBy('order_details_count', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Получить фильтры для категории (бренды, диапазоны цен)
     */
    public static function getCategoryFilters(int $categoryId): array
    {
        return Cache::remember("catalog:category_filters:{$categoryId}", self::CACHE_TTL_MEDIUM, function () use ($categoryId) {
            // Получаем бренды товаров в категории
            $brandIds = Product::where('status', 1)
                ->where('category_ids', 'like', '%"' . $categoryId . '"%')
                ->whereNotNull('brand_id')
                ->distinct()
                ->pluck('brand_id');

            $brands = Brand::whereIn('id', $brandIds)
                ->where('status', 1)
                ->get(['id', 'name', 'slug']);

            // Диапазоны цен
            $priceRanges = self::getPriceRanges($categoryId);

            return [
                'brands' => $brands->toArray(),
                'price_range' => $priceRanges,
            ];
        });
    }

    /**
     * Очистить весь кеш каталога
     */
    public static function clearAll(): void
    {
        Cache::tags(['catalog'])->flush();

        // Очистка специфичных ключей
        $patterns = [
            'catalog:*',
        ];

        foreach ($patterns as $pattern) {
            $keys = Cache::get($pattern, []);
            if (is_array($keys)) {
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
        }
    }

    /**
     * Очистить кеш категорий
     */
    public static function clearCategories(): void
    {
        Cache::forget('catalog:categories_tree');
        Cache::forget('catalog:parent_categories');

        // Удалить кеш конкретных категорий
        $slugs = Category::pluck('slug');
        foreach ($slugs as $slug) {
            Cache::forget("catalog:category_slug:{$slug}");
        }
    }

    /**
     * Очистить кеш брендов
     */
    public static function clearBrands(): void
    {
        Cache::forget('catalog:brands_all');

        $slugs = Brand::pluck('slug');
        foreach ($slugs as $slug) {
            Cache::forget("catalog:brand_slug:{$slug}");
        }
    }

    /**
     * Очистить кеш фильтров категории
     */
    public static function clearCategoryFilters(int $categoryId): void
    {
        Cache::forget("catalog:category_filters:{$categoryId}");
        Cache::forget("catalog:price_ranges_cat:{$categoryId}");
    }

    /**
     * Прогрев кеша (заполнение наиболее используемых данных)
     */
    public static function warmup(): array
    {
        $warmed = [];

        // Прогреваем категории
        self::getCategoriesTree();
        $warmed[] = 'categories_tree';

        self::getParentCategories();
        $warmed[] = 'parent_categories';

        // Прогреваем бренды
        self::getAllBrands();
        $warmed[] = 'brands';

        // Прогреваем популярные товары
        self::getTrendingProducts(10);
        $warmed[] = 'trending_products';

        // Прогреваем диапазоны цен
        self::getPriceRanges();
        $warmed[] = 'price_ranges';

        return $warmed;
    }
}
