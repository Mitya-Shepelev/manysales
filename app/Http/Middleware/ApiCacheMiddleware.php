<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для кеширования API ответов
 * Значительно ускоряет работу мобильного приложения
 */
class ApiCacheMiddleware
{
    /**
     * Время жизни кеша по умолчанию (в секундах)
     */
    const DEFAULT_TTL = 300; // 5 минут

    /**
     * Эндпоинты, которые нужно кешировать, и их TTL
     */
    const CACHEABLE_ENDPOINTS = [
        'api/v1/products/latest' => 300,           // 5 минут
        'api/v1/products/featured' => 600,         // 10 минут
        'api/v1/products/top-rated' => 600,        // 10 минут
        'api/v1/categories' => 3600,               // 1 час
        'api/v1/brands' => 3600,                   // 1 час
        'api/v1/config' => 1800,                   // 30 минут
        'api/v2/products' => 300,                  // 5 минут
        'api/v3/products' => 300,                  // 5 минут
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Кешируем только GET запросы
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Проверяем, нужно ли кешировать этот эндпоинт
        $cacheKey = $this->getCacheKey($request);
        $ttl = $this->getCacheTTL($request);

        if (!$cacheKey || !$ttl) {
            return $next($request);
        }

        // Пытаемся получить из кеша
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse !== null) {
            return response()->json($cachedResponse)
                ->header('X-Cache', 'HIT')
                ->header('X-Cache-Key', $cacheKey);
        }

        // Получаем ответ
        $response = $next($request);

        // Кешируем только успешные ответы
        if ($response->getStatusCode() === 200) {
            $content = json_decode($response->getContent(), true);

            if ($content !== null) {
                Cache::put($cacheKey, $content, $ttl);
            }

            $response->header('X-Cache', 'MISS');
            $response->header('X-Cache-Key', $cacheKey);
        }

        return $response;
    }

    /**
     * Генерация ключа кеша на основе запроса
     */
    private function getCacheKey(Request $request): ?string
    {
        $path = $request->path();

        // Проверяем, нужно ли кешировать этот путь
        if (!$this->shouldCache($path)) {
            return null;
        }

        // Включаем параметры запроса в ключ
        $queryParams = $request->query();
        ksort($queryParams); // Сортируем для консистентности

        // Включаем язык и валюту в ключ
        $locale = $request->header('X-localization', 'ru');
        $currency = $request->header('currency-code', 'RUB');

        // Для авторизованных пользователей не кешируем персональные данные
        $userId = auth('api')->id() ?? 'guest';

        $keyParts = [
            'api_cache',
            $path,
            $locale,
            $currency,
            md5(json_encode($queryParams)),
        ];

        // Для неавторизованных можно кешировать глобально
        if ($userId === 'guest') {
            return implode(':', $keyParts);
        }

        // Для авторизованных - персональный кеш (для wishlist, cart и т.д.)
        $keyParts[] = $userId;
        return implode(':', $keyParts);
    }

    /**
     * Проверить, нужно ли кешировать этот путь
     */
    private function shouldCache(string $path): bool
    {
        foreach (array_keys(self::CACHEABLE_ENDPOINTS) as $cacheablePath) {
            if (str_starts_with($path, $cacheablePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить TTL для данного пути
     */
    private function getCacheTTL(Request $request): ?int
    {
        $path = $request->path();

        foreach (self::CACHEABLE_ENDPOINTS as $cacheablePath => $ttl) {
            if (str_starts_with($path, $cacheablePath)) {
                return $ttl;
            }
        }

        return null;
    }
}
