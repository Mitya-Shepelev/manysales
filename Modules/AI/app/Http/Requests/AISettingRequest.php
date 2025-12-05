<?php

namespace Modules\AI\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AISettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'ai_provider' => ['required', 'string', 'in:OpenAI,OpenRouter'],
            'api_key' => ['nullable', 'required_if:status,1', 'string'],
            'openrouter_model' => ['nullable', 'string'],
        ];

        // organization_id is only required for OpenAI provider
        if ($this->input('ai_provider') === 'OpenAI') {
            $rules['organization_id'] = ['nullable', 'required_if:status,1', 'string'];
        } else {
            $rules['organization_id'] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'api_key.required_if' => translate('The API Key is required when status is enabled.'),
            'organization_id.required_if' => translate('The Organization ID is required when status is enabled for OpenAI.'),
        ];
    }
}
