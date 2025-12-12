<div class="card mt-3 rest-part">
    <div class="card-header">
        <div class="d-flex gap-2">
            <i class="fi fi-sr-user"></i>
            <h3 class="mb-0">{{ translate('product_video') }}</h3>
            <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                  aria-label="{{ translate('select_video_provider_and_add_video_link') }}"
                  data-bs-title="{{ translate('select_video_provider_and_add_video_link') }}"
            >
                <i class="fi fi-sr-info"></i>
            </span>
        </div>
    </div>
    <div class="card-body">
        {{-- Video Provider Selection --}}
        <div class="mb-3">
            <label class="form-label">
                {{ translate('video_provider') }}
            </label>
            <select name="video_provider" id="video_provider" class="form-control">
                <option value="">{{ translate('select_provider') }}</option>
                <option value="youtube">YouTube</option>
                <option value="kinescope">Kinescope</option>
            </select>
        </div>

        {{-- Video URL Input --}}
        <div class="mb-3">
            <label class="form-label mb-0">
                {{ translate('video_link') }}
            </label>
            <span class="text-info" id="video_url_hint">
                {{ translate('select_provider_first') }}
            </span>
        </div>
        <input type="text"
               name="video_url"
               id="video_url"
               placeholder="{{ translate('select_provider_first') }}"
               class="form-control"
               disabled>

        {{-- Help Text --}}
        <div class="mt-2">
            <small class="text-muted" id="video_url_examples" style="display: none;">
                <strong id="examples_label"></strong>
                <ul id="examples_list" class="mb-0 ps-3"></ul>
            </small>
        </div>
    </div>
</div>

@push('script')
<script src="{{ asset('public/assets/back-end/js/product-video-provider.js') }}"></script>
@endpush
