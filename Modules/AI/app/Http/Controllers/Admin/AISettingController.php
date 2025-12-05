<?php

namespace Modules\AI\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Modules\AI\AIProviders\OpenRouterProvider;
use Modules\AI\app\Http\Requests\AISettingRequest;
use Modules\AI\app\Http\Requests\AIVendorUsagesLimitRequest;
use Modules\AI\app\Models\AISetting;

class AISettingController extends Controller
{

    public function index()
    {
        $AiSetting = AISetting::first();
        $openRouterModels = OpenRouterProvider::getModelsForSelect();
        $providers = [
            'OpenAI' => 'OpenAI',
            'OpenRouter' => 'OpenRouter',
        ];
        return view('ai::admin-views.ai-setting.index', compact('AiSetting', 'openRouterModels', 'providers'));
    }

    /**
     * Refresh OpenRouter models list from API
     */
    public function refreshOpenRouterModels()
    {
        try {
            $models = OpenRouterProvider::refreshModels();
            return response()->json([
                'success' => true,
                'models' => OpenRouterProvider::getModelsForSelect(),
                'count' => count($models),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getVendorUsagesLimitView()
    {
        $AiSetting = AISetting::first();
        return view('ai::admin-views.ai-setting.vendors-usage-limits', compact('AiSetting'));
    }


    public function store(AISettingRequest $request): RedirectResponse
    {
        Cache::forget('active_ai_provider');
        self::addFirstAISetting();

        try {
            $AiSetting = AISetting::first();
            $provider = $request['ai_provider'] ?? 'OpenAI';

            // Build settings array
            $settings = $AiSetting->settings ?? [];
            if ($provider === 'OpenRouter') {
                $settings['model'] = $request['openrouter_model'] ?? 'openai/gpt-4o-mini';
            }

            // For OpenRouter, organization_id is not required
            $orgIdRequired = $provider === 'OpenAI';
            $hasValidConfig = !empty($request['api_key']) &&
                ($provider === 'OpenRouter' || !empty($request['organization_id']));

            $AiSetting->update([
                'ai_name' => $provider,
                'api_key' => $request['api_key'],
                'organization_id' => $provider === 'OpenAI' ? $request['organization_id'] : null,
                'settings' => $settings,
                'status' => $hasValidConfig && $request['status'] == 1 ? 1 : 0,
            ]);

            ToastMagic::success(translate('AI_configuration_saved_successfully'));
        } catch (Exception $exception) {
            ToastMagic::error(translate('Failed_to_save_AI_configuration'));
        }
        return redirect()->back();
    }

    public function updateVendorUsagesLimit(AIVendorUsagesLimitRequest $request): RedirectResponse
    {
        Cache::forget('active_ai_provider');
        self::addFirstAISetting();

        try {
            $AiSetting = AISetting::first();
            $AiSetting->update([
                'image_upload_limit' => $request['image_upload_limit'] ?? 0,
                'generate_limit' => $request['generate_limit'] ?? 0
            ]);

            ToastMagic::success(translate('AI_configuration_saved_successfully'));
        } catch (Exception $exception) {
            ToastMagic::error(translate('Failed_to_save_AI_configuration'));
        }
        return redirect()->back();
    }


    public function addFirstAISetting(): void
    {
        Cache::forget('active_ai_provider');
        if (!AISetting::first()) {
            AISetting::create([
                'ai_name' => 'OpenAI',
                'api_key' => '',
                'organization_id' => '',
                'image_upload_limit' => 0,
                'generate_limit' => 0,
                'status' => 0,
            ]);
        }
    }
}
