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
                <option value="youtube" {{ $product->video_provider == 'youtube' ? 'selected' : '' }}>YouTube</option>
                <option value="kinescope" {{ $product->video_provider == 'kinescope' ? 'selected' : '' }}>Kinescope</option>
            </select>
        </div>

        {{-- Video URL Input --}}
        <div class="mb-3">
            <label class="form-label mb-0">
                {{ translate('video_link') }}
            </label>
            <span class="text-info" id="video_url_hint">
                @if($product->video_provider == 'youtube')
                    ({{ translate('use_embed_link_not_direct') }})
                @elseif($product->video_provider == 'kinescope')
                    ({{ translate('any_kinescope_link_format') }})
                @else
                    {{ translate('select_provider_first') }}
                @endif
            </span>
        </div>
        <input type="text"
               name="video_url"
               id="video_url"
               value="{{ $product->video_url }}"
               placeholder="@if($product->video_provider == 'youtube')Ex: https://www.youtube.com/embed/VIDEO_ID@elseif($product->video_provider == 'kinescope')Ex: https://kinescope.io/VIDEO_ID@else{{ translate('select_provider_first') }}@endif"
               class="form-control"
               data-current-provider="{{ $product->video_provider }}"
               {{ $product->video_provider ? '' : 'disabled' }}>

        {{-- Help Text --}}
        <div class="mt-2">
            <small class="text-muted" id="video_url_examples" style="{{ $product->video_provider ? 'display: block;' : 'display: none;' }}">
                <strong id="examples_label">
                    @if($product->video_provider == 'youtube')
                        Примеры ссылок для YouTube:
                    @elseif($product->video_provider == 'kinescope')
                        Примеры ссылок для Kinescope:
                    @endif
                </strong>
                <ul id="examples_list" class="mb-0 ps-3">
                    @if($product->video_provider == 'youtube')
                        <li>https://www.youtube.com/embed/5R06LRdUCSE</li>
                        <li>https://youtube.com/embed/VIDEO_ID</li>
                    @elseif($product->video_provider == 'kinescope')
                        <li>https://kinescope.io/VIDEO_ID</li>
                        <li>https://kinescope.io/embed/VIDEO_ID</li>
                        <li>VIDEO_ID (только ID)</li>
                    @endif
                </ul>
            </small>
        </div>
    </div>
</div>

@push('script')
<script src="{{ asset('public/assets/back-end/js/product-video-provider.js') }}"></script>
@endpush
