@extends('layouts.admin.app')

@section('title', translate('AI_Configuration'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                {{ translate('AI_Setup') }}
            </h2>
        </div>

        @include('ai::admin-views.ai-setting._ai-setup-menu')

        <div class="card card-body">
            <form action="{{ route('admin.third-party.ai-setting.store') }}" method="post" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate>
                @csrf
                <div class="view-details-container">
                    <div>
                        <h3 class="mb-1">
                            {{ translate('AI_Configuration') }}
                        </h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Fill_up_the_necessary_info_to_activate_AI_feature') }}
                        </p>
                    </div>
                    <div class="mt-3">
                        <div
                            class="d-flex justify-content-between align-items-center gap-3 border rounded px-20 py-2 user-select-none">
                            <span class="fw-semibold text-dark">{{ translate('AI_Status') }}</span>
                            <label class="switcher" for="ai-status-id">
                                <input class="switcher_input custom-modal-plugin" type="checkbox" value="1"
                                       {{ $AiSetting && $AiSetting->status== 1 ? 'checked':''}}
                                       name="status" id="ai-status-id"
                                       data-modal-type="input-change"
                                       data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance_mode-on.png') }}"
                                       data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance_mode-off.png') }}"
                                       data-on-title="{{ translate('Do_you_want_to_activate_AI_feature').'?'}}"
                                       data-off-title="{{ translate('Do_you_want_to_deactivate_AI_feature').'?'}}"
                                       data-on-message="<p>{{ translate('Enabling this will activate AI features in admin, vendor panel, and vendor app') }}</p>"
                                       data-off-message="<p>{{ translate('Disabling this will hide AI features from admin, vendor panel, and vendor app') }}</p>"
                                       data-on-button-text="{{ translate('Activate') }}"
                                       data-off-button-text="{{ translate('Deactivate') }}">
                                <span class="switcher_control"></span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-3 mt-sm-4">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="ai_provider">
                                            {{ translate('AI_Provider') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select name="ai_provider" id="ai_provider" class="form-control" required>
                                            @foreach($providers as $key => $name)
                                                <option value="{{ $key }}" {{ ($AiSetting->ai_name ?? 'OpenAI') === $key ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6" id="openrouter_model_wrapper" style="{{ ($AiSetting->ai_name ?? 'OpenAI') === 'OpenRouter' ? '' : 'display: none;' }}">
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="openrouter_model">
                                            {{ translate('AI_Model') }}
                                            <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                aria-label="{{ translate('Select the AI model to use for content generation') }}"
                                                data-bs-title="{{ translate('Select the AI model to use for content generation') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="refresh_models_btn" title="{{ translate('Refresh_models_list') }}">
                                                <i class="fi fi-rr-refresh" id="refresh_icon"></i>
                                            </button>
                                        </label>
                                        <select name="openrouter_model" id="openrouter_model" class="form-control js-select2-custom" data-live-search="true">
                                            @foreach($openRouterModels as $modelKey => $modelName)
                                                <option value="{{ $modelKey }}" {{ ($AiSetting->settings['model'] ?? 'openai/gpt-4o-mini') === $modelKey ? 'selected' : '' }}>
                                                    {{ $modelName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">{{ translate('Models_marked_FREE_have_no_cost') }}. {{ translate('Total') }}: {{ count($openRouterModels) }} {{ translate('models') }}</small>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="api_key">
                                            <span id="api_key_label">{{ ($AiSetting->ai_name ?? 'OpenAI') === 'OpenRouter' ? translate('OpenRouter_API_Key') : translate('OpenAI_API_Key') }}</span>
                                            <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                aria-label="{{ translate('Get your API key from the provider and enter it here') }}"
                                                data-bs-title="{{ translate('Get your API key from the provider and enter it here') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <input type="text" id="api_key" name="api_key" class="form-control"
                                               value="{{ showDemoModeInputValue(value: $AiSetting->api_key ?? '') }}"
                                               data-required-msg="{{translate('api_key_field_is_required')}}"
                                               placeholder="{{ translate('Type_API_Key') }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-6" id="organization_id_wrapper" style="{{ ($AiSetting->ai_name ?? 'OpenAI') === 'OpenRouter' ? 'display: none;' : '' }}">
                                    <div class="form-group mb-0">
                                        <label class="form-label" for="">{{ translate('OpenAI_Organization_ID') }}
                                            <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                  aria-label="{{ translate('Get your OpenAI Organization ID and enter it here for access and billing') }}"
                                                  data-bs-title="{{ translate('Get your OpenAI Organization ID and enter it here for access and billing.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <input type="text" id="organization_id" name="organization_id"
                                               class="form-control"
                                               data-required-msg="{{translate('organization_id_field_is_required')}}"
                                               value="{{ showDemoModeInputValue(value: $AiSetting->organization_id ?? '') }}"
                                               placeholder="{{ translate('Type_Organization_Id') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end flex-wrap gap-3 mt-4">
                            <button type="reset" class="btn btn-secondary w-120 px-4">
                                {{ translate('reset') }}
                            </button>
                            <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                    class="btn btn-primary w-120 px-4 {{ getDemoModeFormButton(type: 'class') }}">
                                {{ translate('save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div
        class="d-flex gap-2 bg-white p-2 position-fixed inset-inline-end-0 pointer shadow view-guideline-btn flex-column align-items-center"
        data-bs-toggle="offcanvas" data-bs-target="#offcanvasSetupGuide">
        <span class="bg-primary py-1 px-2 text-white rounded fs-12"><i class="fi fi-rr-redo"></i></span>
        <span class="view-guideline-btn-text text-dark fw-medium pb-2 text-nowrap">
            {{ translate('View_Guideline') }}
        </span>
    </div>
    @include('ai::admin-views.ai-setting._ai-setup-view-guideline')

@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const providerSelect = document.getElementById('ai_provider');
        const modelWrapper = document.getElementById('openrouter_model_wrapper');
        const orgIdWrapper = document.getElementById('organization_id_wrapper');
        const apiKeyLabel = document.getElementById('api_key_label');
        const orgIdInput = document.getElementById('organization_id');
        const refreshBtn = document.getElementById('refresh_models_btn');
        const refreshIcon = document.getElementById('refresh_icon');
        const modelSelect = document.getElementById('openrouter_model');

        function toggleFields() {
            const provider = providerSelect.value;

            if (provider === 'OpenRouter') {
                modelWrapper.style.display = '';
                orgIdWrapper.style.display = 'none';
                apiKeyLabel.textContent = '{{ translate("OpenRouter_API_Key") }}';
                orgIdInput.removeAttribute('required');
            } else {
                modelWrapper.style.display = 'none';
                orgIdWrapper.style.display = '';
                apiKeyLabel.textContent = '{{ translate("OpenAI_API_Key") }}';
                orgIdInput.setAttribute('required', 'required');
            }
        }

        providerSelect.addEventListener('change', toggleFields);

        // Refresh models button
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                refreshIcon.classList.add('fa-spin');
                refreshBtn.disabled = true;

                fetch('{{ route("admin.third-party.ai-setting.refresh-openrouter-models") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear and repopulate select
                        const currentValue = modelSelect.value;
                        modelSelect.innerHTML = '';

                        for (const [key, name] of Object.entries(data.models)) {
                            const option = document.createElement('option');
                            option.value = key;
                            option.textContent = name;
                            if (key === currentValue) {
                                option.selected = true;
                            }
                            modelSelect.appendChild(option);
                        }

                        // Reinitialize select2 if used
                        if ($(modelSelect).data('select2')) {
                            $(modelSelect).trigger('change');
                        }

                        toastr.success('{{ translate("Models_list_updated") }}! ' + data.count + ' {{ translate("models_available") }}');
                    } else {
                        toastr.error(data.message || '{{ translate("Failed_to_refresh_models") }}');
                    }
                })
                .catch(error => {
                    toastr.error('{{ translate("Failed_to_refresh_models") }}');
                    console.error(error);
                })
                .finally(() => {
                    refreshIcon.classList.remove('fa-spin');
                    refreshBtn.disabled = false;
                });
            });
        }
    });
</script>
@endpush
