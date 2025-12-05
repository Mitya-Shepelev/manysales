<?php

namespace Modules\AI\AIProviders;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\AI\app\Contracts\AIProviderInterface;
use Modules\AI\app\Models\AISetting;

class OpenRouterProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected ?string $model = null;
    protected string $baseUrl = 'https://openrouter.ai/api/v1';

    /**
     * Fetch available models from OpenRouter API
     * Results are cached for 1 hour
     */
    public static function getAvailableModels(): array
    {
        return Cache::remember('openrouter_models', 3600, function () {
            return self::fetchModelsFromApi();
        });
    }

    /**
     * Force refresh models from API
     */
    public static function refreshModels(): array
    {
        Cache::forget('openrouter_models');
        return self::getAvailableModels();
    }

    /**
     * Fetch models from OpenRouter API
     */
    protected static function fetchModelsFromApi(): array
    {
        try {
            $response = Http::timeout(30)
                ->connectTimeout(15)
                ->retry(3, 1000, function ($exception) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->get('https://openrouter.ai/api/v1/models');

            if ($response->failed()) {
                return self::getFallbackModels();
            }

            $data = $response->json();
            $models = [];

            if (!empty($data['data'])) {
                foreach ($data['data'] as $model) {
                    $id = $model['id'] ?? null;
                    $name = $model['name'] ?? $id;
                    $pricing = $model['pricing'] ?? [];

                    if (!$id) continue;

                    // Calculate price info
                    $promptPrice = floatval($pricing['prompt'] ?? 0);
                    $completionPrice = floatval($pricing['completion'] ?? 0);

                    // Check if free
                    $isFree = ($promptPrice == 0 && $completionPrice == 0);

                    // Format price for display (price per 1M tokens)
                    $priceInfo = $isFree ? 'FREE' : self::formatPrice($promptPrice, $completionPrice);

                    // Get context length
                    $contextLength = $model['context_length'] ?? 0;
                    $contextInfo = $contextLength > 0 ? self::formatContextLength($contextLength) : '';

                    // Build display name
                    $displayName = $name;
                    if ($priceInfo) {
                        $displayName .= " [{$priceInfo}]";
                    }
                    if ($contextInfo) {
                        $displayName .= " ({$contextInfo})";
                    }

                    $models[$id] = [
                        'id' => $id,
                        'name' => $name,
                        'display_name' => $displayName,
                        'is_free' => $isFree,
                        'prompt_price' => $promptPrice,
                        'completion_price' => $completionPrice,
                        'context_length' => $contextLength,
                        'description' => $model['description'] ?? '',
                    ];
                }

                // Sort: free models first, then by name
                uasort($models, function ($a, $b) {
                    if ($a['is_free'] !== $b['is_free']) {
                        return $a['is_free'] ? -1 : 1;
                    }
                    return strcmp($a['name'], $b['name']);
                });
            }

            return !empty($models) ? $models : self::getFallbackModels();

        } catch (\Exception $e) {
            return self::getFallbackModels();
        }
    }

    /**
     * Format price for display (per 1M tokens)
     */
    protected static function formatPrice(float $promptPrice, float $completionPrice): string
    {
        // Prices from API are per token, convert to per 1M tokens
        $promptPer1M = $promptPrice * 1000000;
        $completionPer1M = $completionPrice * 1000000;

        if ($promptPer1M < 0.01 && $completionPer1M < 0.01) {
            return 'FREE';
        }

        return sprintf('$%.2f/1M', ($promptPer1M + $completionPer1M) / 2);
    }

    /**
     * Format context length for display
     */
    protected static function formatContextLength(int $length): string
    {
        if ($length >= 1000000) {
            return round($length / 1000000, 1) . 'M ctx';
        }
        if ($length >= 1000) {
            return round($length / 1000) . 'K ctx';
        }
        return $length . ' ctx';
    }

    /**
     * Get simple list for dropdown (id => display_name)
     */
    public static function getModelsForSelect(): array
    {
        $models = self::getAvailableModels();
        $result = [];

        foreach ($models as $id => $model) {
            $result[$id] = $model['display_name'];
        }

        return $result;
    }

    /**
     * Fallback models if API is unavailable
     */
    protected static function getFallbackModels(): array
    {
        return [
            'openai/gpt-4o-mini' => [
                'id' => 'openai/gpt-4o-mini',
                'name' => 'GPT-4o Mini',
                'display_name' => 'GPT-4o Mini (OpenAI)',
                'is_free' => false,
                'prompt_price' => 0,
                'completion_price' => 0,
                'context_length' => 128000,
                'description' => 'Fast and affordable model from OpenAI',
            ],
            'google/gemini-2.0-flash-exp:free' => [
                'id' => 'google/gemini-2.0-flash-exp:free',
                'name' => 'Gemini 2.0 Flash Exp',
                'display_name' => 'Gemini 2.0 Flash Exp [FREE] (1M ctx)',
                'is_free' => true,
                'prompt_price' => 0,
                'completion_price' => 0,
                'context_length' => 1000000,
                'description' => 'Free experimental model from Google',
            ],
            'meta-llama/llama-3.2-3b-instruct:free' => [
                'id' => 'meta-llama/llama-3.2-3b-instruct:free',
                'name' => 'Llama 3.2 3B Instruct',
                'display_name' => 'Llama 3.2 3B Instruct [FREE] (131K ctx)',
                'is_free' => true,
                'prompt_price' => 0,
                'completion_price' => 0,
                'context_length' => 131072,
                'description' => 'Free Llama model from Meta',
            ],
            'deepseek/deepseek-chat' => [
                'id' => 'deepseek/deepseek-chat',
                'name' => 'DeepSeek Chat',
                'display_name' => 'DeepSeek Chat (64K ctx)',
                'is_free' => false,
                'prompt_price' => 0,
                'completion_price' => 0,
                'context_length' => 65536,
                'description' => 'Powerful chat model from DeepSeek',
            ],
        ];
    }

    public function getName(): string
    {
        return 'OpenRouter';
    }

    public function setApiKey($apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function setOrganization($organization): void
    {
        // OpenRouter doesn't use organization, but we use this to pass the model
        $this->model = $organization;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function generate(string $prompt, ?string $imageUrl = null, array $options = []): string
    {
        $model = $this->getSelectedModel();

        $content = [['type' => 'text', 'text' => $prompt]];

        if (!empty($imageUrl)) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $imageUrl],
            ];
        }

        $maxRetries = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                    'X-Title' => config('app.name', 'ManySales'),
                    'Content-Type' => 'application/json',
                ])
                ->timeout(90)
                ->connectTimeout(30)
                ->retry(2, 1000, function ($exception) {
                    // Retry on connection errors (including SSL errors)
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $content,
                        ],
                    ],
                    'temperature' => 0.3,
                ]);

                if ($response->failed()) {
                    $error = $response->json('error.message', 'Unknown error from OpenRouter API');
                    throw new \Exception('OpenRouter API Error: ' . $error);
                }

                $data = $response->json();

                return $data['choices'][0]['message']['content'] ?? '';

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastException = $e;
                // Wait before retry with exponential backoff
                if ($attempt < $maxRetries) {
                    sleep($attempt * 2);
                }
            } catch (\Exception $e) {
                // For non-connection errors, don't retry
                throw $e;
            }
        }

        // If all retries failed, throw the last exception with a helpful message
        throw new \Exception(
            'Failed to connect to OpenRouter API after ' . $maxRetries . ' attempts. ' .
            'Please check your internet connection and try again. ' .
            'Error: ' . ($lastException ? $lastException->getMessage() : 'Unknown connection error')
        );
    }

    /**
     * Get the selected model from settings or use default
     */
    protected function getSelectedModel(): string
    {
        if ($this->model) {
            return $this->model;
        }

        // Try to get from settings
        $aiSetting = AISetting::first();
        if ($aiSetting && !empty($aiSetting->settings)) {
            $settings = is_array($aiSetting->settings)
                ? $aiSetting->settings
                : json_decode($aiSetting->settings, true);

            if (!empty($settings['model'])) {
                return $settings['model'];
            }
        }

        // Default model
        return 'openai/gpt-4o-mini';
    }
}
