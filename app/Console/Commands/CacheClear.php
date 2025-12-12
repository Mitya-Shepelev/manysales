<?php

namespace App\Console\Commands;

use App\Services\CatalogCacheService;
use App\Services\VendorCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-app
                            {--type=all : Тип кеша для очистки (all|catalog|vendor|api)}
                            {--vendor= : ID продавца для очистки его кеша}
                            {--warmup : Прогреть кеш после очистки}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистка кеша приложения (каталог, продавцы, API)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $vendorId = $this->option('vendor');
        $warmup = $this->option('warmup');

        $this->info('🧹 Очистка кеша приложения...');

        match ($type) {
            'catalog' => $this->clearCatalog(),
            'vendor' => $this->clearVendor($vendorId),
            'api' => $this->clearApi(),
            'all' => $this->clearAll(),
            default => $this->error("Неизвестный тип: {$type}"),
        };

        if ($warmup && $type !== 'vendor') {
            $this->warmupCache();
        }

        $this->newLine();
        $this->info('✅ Кеш успешно очищен!');

        return Command::SUCCESS;
    }

    /**
     * Очистить кеш каталога
     */
    private function clearCatalog(): void
    {
        $this->line('📦 Очистка кеша каталога...');

        CatalogCacheService::clearAll();
        Cache::forget('categories:parents_with_children');

        $this->info('  ✓ Кеш каталога очищен');
    }

    /**
     * Очистить кеш продавца
     */
    private function clearVendor(?string $vendorId): void
    {
        if (!$vendorId) {
            $this->error('  ✗ Укажите ID продавца: --vendor=123');
            return;
        }

        $this->line("🏪 Очистка кеша продавца #{$vendorId}...");

        VendorCacheService::clearVendor((int)$vendorId);

        $this->info("  ✓ Кеш продавца #{$vendorId} очищен");
    }

    /**
     * Очистить кеш API
     */
    private function clearApi(): void
    {
        $this->line('🔌 Очистка кеша API...');

        // Удаляем все ключи, начинающиеся с api_cache:
        $keys = Cache::get('api_cache_keys', []);

        $count = 0;
        foreach ($keys as $key) {
            if (str_starts_with($key, 'api_cache:')) {
                Cache::forget($key);
                $count++;
            }
        }

        // Альтернативный способ через паттерн (если Redis)
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $pattern = Cache::getStore()->getPrefix() . 'api_cache:*';

            $keys = $redis->keys($pattern);
            foreach ($keys as $key) {
                $redis->del($key);
                $count++;
            }
        }

        $this->info("  ✓ Очищено {$count} ключей API");
    }

    /**
     * Очистить весь кеш
     */
    private function clearAll(): void
    {
        $this->clearCatalog();
        $this->clearApi();

        $this->line('🗑️  Очистка всего application кеша...');
        Cache::flush();

        $this->info('  ✓ Весь кеш очищен');
    }

    /**
     * Прогрев кеша (заполнение часто используемых данных)
     */
    private function warmupCache(): void
    {
        $this->newLine();
        $this->line('🔥 Прогрев кеша...');

        $bar = $this->output->createProgressBar(5);
        $bar->start();

        // Прогреваем каталог
        $warmed = CatalogCacheService::warmup();
        $bar->advance();

        $bar->finish();

        $this->newLine();
        $this->info('  ✓ Прогрето: ' . implode(', ', $warmed));
    }
}
