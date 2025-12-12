# Kinescope Video Player Integration - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Integrate Kinescope video player into ManySales product pages, allowing admins and vendors to add Kinescope videos alongside existing YouTube support.

**Architecture:** Create KinescopeService for URL parsing, update admin/vendor forms with provider dropdown, display player on product detail pages above description. No database migrations needed - uses existing video_provider and video_url fields.

**Tech Stack:** Laravel 12, PHP 8.2+, Blade templates, JavaScript (vanilla), PHPUnit

---

## Task 1: Create KinescopeService with URL Parsing

**Files:**
- Create: `app/Services/KinescopeService.php`
- Create: `tests/Unit/Services/KinescopeServiceTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Services/KinescopeServiceTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Services\KinescopeService;
use Tests\TestCase;

class KinescopeServiceTest extends TestCase
{
    /**
     * Test parsing embed URL format
     */
    public function test_parse_embed_url_format(): void
    {
        $url = 'https://kinescope.io/embed/abc123xyz';
        $result = KinescopeService::parseVideoUrl($url);

        $this->assertEquals('abc123xyz', $result);
    }

    /**
     * Test parsing play URL format
     */
    public function test_parse_play_url_format(): void
    {
        $url = 'https://kinescope.io/abc123xyz';
        $result = KinescopeService::parseVideoUrl($url);

        $this->assertEquals('abc123xyz', $result);
    }

    /**
     * Test parsing video ID only
     */
    public function test_parse_video_id_only(): void
    {
        $videoId = 'abc123xyz';
        $result = KinescopeService::parseVideoUrl($videoId);

        $this->assertEquals('abc123xyz', $result);
    }

    /**
     * Test parsing with www prefix
     */
    public function test_parse_with_www_prefix(): void
    {
        $url = 'https://www.kinescope.io/embed/abc123xyz';
        $result = KinescopeService::parseVideoUrl($url);

        $this->assertEquals('abc123xyz', $result);
    }

    /**
     * Test parsing with trailing slash
     */
    public function test_parse_with_trailing_slash(): void
    {
        $url = 'https://kinescope.io/abc123xyz/';
        $result = KinescopeService::parseVideoUrl($url);

        $this->assertEquals('abc123xyz', $result);
    }

    /**
     * Test parsing invalid URL returns null
     */
    public function test_parse_invalid_url_returns_null(): void
    {
        $url = 'https://youtube.com/watch?v=invalid';
        $result = KinescopeService::parseVideoUrl($url);

        $this->assertNull($result);
    }

    /**
     * Test generating embed URL
     */
    public function test_get_embed_url(): void
    {
        $videoId = 'abc123xyz';
        $result = KinescopeService::getEmbedUrl($videoId);

        $this->assertEquals('https://kinescope.io/embed/abc123xyz', $result);
    }

    /**
     * Test validating correct Kinescope URL
     */
    public function test_validate_url_returns_true_for_valid(): void
    {
        $url = 'https://kinescope.io/embed/abc123xyz';
        $result = KinescopeService::validateUrl($url);

        $this->assertTrue($result);
    }

    /**
     * Test validating invalid URL
     */
    public function test_validate_url_returns_false_for_invalid(): void
    {
        $url = 'https://youtube.com/watch';
        $result = KinescopeService::validateUrl($url);

        $this->assertFalse($result);
    }

    /**
     * Test validating empty string
     */
    public function test_validate_url_returns_false_for_empty(): void
    {
        $result = KinescopeService::validateUrl('');

        $this->assertFalse($result);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/KinescopeServiceTest.php`

Expected: FAIL with "Class 'App\Services\KinescopeService' not found"

**Step 3: Write minimal implementation**

Create `app/Services/KinescopeService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class KinescopeService
{
    /**
     * Parse Kinescope video URL and extract video ID
     *
     * Supports formats:
     * - https://kinescope.io/embed/VIDEO_ID
     * - https://kinescope.io/VIDEO_ID
     * - VIDEO_ID (only)
     * - with or without www
     * - with or without trailing slash
     *
     * @param string $url The URL or video ID
     * @return string|null The extracted video ID or null if invalid
     */
    public static function parseVideoUrl(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Trim whitespace and trailing slash
        $url = trim($url, " \t\n\r\0\x0B/");

        // Pattern 1: https://kinescope.io/embed/VIDEO_ID
        if (preg_match('/^https?:\/\/(?:www\.)?kinescope\.io\/embed\/([a-zA-Z0-9_-]+)$/i', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 2: https://kinescope.io/VIDEO_ID
        if (preg_match('/^https?:\/\/(?:www\.)?kinescope\.io\/([a-zA-Z0-9_-]+)$/i', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 3: Just VIDEO_ID (alphanumeric, underscore, hyphen)
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $url)) {
            // Must be at least 3 characters to avoid false positives
            if (strlen($url) >= 3) {
                return $url;
            }
        }

        // Log invalid URL attempt
        Log::info('Failed to parse Kinescope URL', ['url' => $url]);

        return null;
    }

    /**
     * Generate embed URL from video ID
     *
     * @param string $videoId The Kinescope video ID
     * @return string The embed URL
     */
    public static function getEmbedUrl(string $videoId): string
    {
        return "https://kinescope.io/embed/{$videoId}";
    }

    /**
     * Validate if URL is a valid Kinescope URL or video ID
     *
     * @param string $url The URL or video ID to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateUrl(string $url): bool
    {
        return self::parseVideoUrl($url) !== null;
    }
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/KinescopeServiceTest.php`

Expected: All tests PASS (10 tests)

**Step 5: Commit**

```bash
git add app/Services/KinescopeService.php tests/Unit/Services/KinescopeServiceTest.php
git commit -m "feat: add KinescopeService for video URL parsing

- Parse embed, play, and ID-only formats
- Support www prefix and trailing slashes
- Validate Kinescope URLs
- Generate embed URLs
- Comprehensive unit tests (10 tests)

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Task 2: Update Admin Product Video Form Partial

**Files:**
- Modify: `resources/views/admin-views/product/add/_product-video.blade.php`
- Create: `public/assets/back-end/js/product-video-provider.js`

**Step 1: Update form partial with provider dropdown**

Replace content of `resources/views/admin-views/product/add/_product-video.blade.php`:

```blade
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
```

**Step 2: Create JavaScript for dynamic form behavior**

Create `public/assets/back-end/js/product-video-provider.js`:

```javascript
/**
 * Product Video Provider Handler
 * Manages dynamic behavior for video provider selection
 */
(function() {
    'use strict';

    const PROVIDERS = {
        youtube: {
            name: 'YouTube',
            placeholder: 'Ex: https://www.youtube.com/embed/VIDEO_ID',
            hint: '(Используйте embed ссылку, не прямую)',
            examples: [
                'https://www.youtube.com/embed/5R06LRdUCSE',
                'https://youtube.com/embed/VIDEO_ID'
            ]
        },
        kinescope: {
            name: 'Kinescope',
            placeholder: 'Ex: https://kinescope.io/VIDEO_ID',
            hint: '(Можно вставить любой формат ссылки)',
            examples: [
                'https://kinescope.io/VIDEO_ID',
                'https://kinescope.io/embed/VIDEO_ID',
                'VIDEO_ID (только ID)'
            ]
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const providerSelect = document.getElementById('video_provider');
        const urlInput = document.getElementById('video_url');
        const hintText = document.getElementById('video_url_hint');
        const examplesBlock = document.getElementById('video_url_examples');
        const examplesLabel = document.getElementById('examples_label');
        const examplesList = document.getElementById('examples_list');

        if (!providerSelect || !urlInput) {
            return;
        }

        // Handle provider change
        providerSelect.addEventListener('change', function() {
            const provider = this.value;

            if (!provider) {
                // No provider selected
                urlInput.disabled = true;
                urlInput.placeholder = 'Выберите провайдер сначала';
                urlInput.value = '';
                hintText.textContent = 'Выберите провайдер сначала';
                examplesBlock.style.display = 'none';
                return;
            }

            const config = PROVIDERS[provider];
            if (!config) {
                console.error('Unknown provider:', provider);
                return;
            }

            // Enable input and update UI
            urlInput.disabled = false;
            urlInput.placeholder = config.placeholder;
            hintText.textContent = config.hint;

            // Update examples
            examplesLabel.textContent = `Примеры ссылок для ${config.name}:`;
            examplesList.innerHTML = config.examples
                .map(example => `<li>${example}</li>`)
                .join('');
            examplesBlock.style.display = 'block';

            // Clear input if switching providers
            if (urlInput.value && urlInput.dataset.currentProvider !== provider) {
                if (confirm('Сменить провайдер? Текущая ссылка будет очищена.')) {
                    urlInput.value = '';
                } else {
                    // Revert provider selection
                    providerSelect.value = urlInput.dataset.currentProvider || '';
                    return;
                }
            }

            urlInput.dataset.currentProvider = provider;
        });

        // Trigger change event if provider already selected (edit mode)
        if (providerSelect.value) {
            providerSelect.dispatchEvent(new Event('change'));
        }
    });
})();
```

**Step 3: Test form manually**

1. Navigate to admin product add page
2. Verify provider dropdown shows "Выбрать провайдер", "YouTube", "Kinescope"
3. Verify URL input is disabled by default
4. Select YouTube - verify input enables with YouTube placeholder
5. Select Kinescope - verify input enables with Kinescope placeholder
6. Verify examples display correctly for each provider

**Step 4: Commit**

```bash
git add resources/views/admin-views/product/add/_product-video.blade.php public/assets/back-end/js/product-video-provider.js
git commit -m "feat(admin): add video provider dropdown to product form

- Add provider selection (YouTube/Kinescope)
- Dynamic placeholder and hints based on provider
- Show format examples for each provider
- Disable URL input until provider selected
- Confirm before switching providers with existing URL

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Task 3: Update Admin Product Video Edit Form Partial

**Files:**
- Modify: `resources/views/admin-views/product/update/_product-video.blade.php`

**Step 1: Update edit form partial**

Replace content of `resources/views/admin-views/product/update/_product-video.blade.php`:

```blade
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
```

**Step 2: Test edit form manually**

1. Create a test product with YouTube video
2. Navigate to product edit page
3. Verify YouTube is pre-selected and URL is populated
4. Switch to Kinescope - verify confirmation prompt
5. Create a test product with Kinescope video
6. Navigate to edit page
7. Verify Kinescope is pre-selected and URL is populated

**Step 3: Commit**

```bash
git add resources/views/admin-views/product/update/_product-video.blade.php
git commit -m "feat(admin): add provider dropdown to product edit form

- Pre-select existing video provider
- Pre-populate video URL
- Use same JavaScript handler as add form
- Show current provider examples

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Task 4: Update Vendor Product Forms

**Files:**
- Modify: `resources/views/vendor-views/product/add-new.blade.php`
- Modify: `resources/views/vendor-views/product/edit.blade.php`

**Step 1: Find video section in vendor add form**

Run: `grep -n "video_url" resources/views/vendor-views/product/add-new.blade.php`

This will show the line numbers where video section is located.

**Step 2: Update vendor add-new form video section**

Find the video section in `resources/views/vendor-views/product/add-new.blade.php` (around line 800-850 based on the file structure).

Replace the video input section with:

```blade
{{-- Video Provider --}}
<div class="col-md-6 col-lg-4 col-xl-3">
    <div class="form-group">
        <label class="title-color">{{ translate('video_provider') }}</label>
        <select name="video_provider" id="video_provider" class="form-control">
            <option value="">{{ translate('select_provider') }}</option>
            <option value="youtube">YouTube</option>
            <option value="kinescope">Kinescope</option>
        </select>
    </div>
</div>

{{-- Video URL --}}
<div class="col-md-6 col-lg-8 col-xl-9">
    <div class="form-group">
        <label class="title-color">
            {{ translate('video_link') }}
            <span class="text-info small" id="video_url_hint">
                ({{ translate('optional') }})
            </span>
        </label>
        <input type="text"
               name="video_url"
               id="video_url"
               placeholder="{{ translate('select_provider_first') }}"
               class="form-control"
               disabled>
        <small class="text-muted" id="video_url_examples" style="display: none;">
            <span id="examples_label"></span>
        </small>
    </div>
</div>

@push('script')
<script src="{{ asset('public/assets/back-end/js/product-video-provider.js') }}"></script>
@endpush
```

**Step 3: Update vendor edit form video section**

Find the video section in `resources/views/vendor-views/product/edit.blade.php`.

Replace with:

```blade
{{-- Video Provider --}}
<div class="col-md-6 col-lg-4 col-xl-3">
    <div class="form-group">
        <label class="title-color">{{ translate('video_provider') }}</label>
        <select name="video_provider" id="video_provider" class="form-control">
            <option value="">{{ translate('select_provider') }}</option>
            <option value="youtube" {{ $product->video_provider == 'youtube' ? 'selected' : '' }}>YouTube</option>
            <option value="kinescope" {{ $product->video_provider == 'kinescope' ? 'selected' : '' }}>Kinescope</option>
        </select>
    </div>
</div>

{{-- Video URL --}}
<div class="col-md-6 col-lg-8 col-xl-9">
    <div class="form-group">
        <label class="title-color">
            {{ translate('video_link') }}
            <span class="text-info small" id="video_url_hint">
                ({{ translate('optional') }})
            </span>
        </label>
        <input type="text"
               name="video_url"
               id="video_url"
               value="{{ $product->video_url }}"
               placeholder="{{ translate('select_provider_first') }}"
               class="form-control"
               data-current-provider="{{ $product->video_provider }}"
               {{ $product->video_provider ? '' : 'disabled' }}>
        <small class="text-muted" id="video_url_examples" style="display: none;">
            <span id="examples_label"></span>
        </small>
    </div>
</div>

@push('script')
<script src="{{ asset('public/assets/back-end/js/product-video-provider.js') }}"></script>
@endpush
```

**Step 4: Test vendor forms manually**

1. Login as vendor
2. Navigate to add product page
3. Test provider dropdown and URL input
4. Save product with Kinescope video
5. Edit the product
6. Verify provider and URL are pre-populated

**Step 5: Commit**

```bash
git add resources/views/vendor-views/product/add-new.blade.php resources/views/vendor-views/product/edit.blade.php
git commit -m "feat(vendor): add video provider support to product forms

- Add provider dropdown to add/edit forms
- Reuse product-video-provider.js from admin
- Support Kinescope and YouTube
- Pre-populate values in edit mode

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Task 5: Update ProductService Validation

**Files:**
- Modify: `app/Services/ProductService.php`

**Step 1: Find product store/update methods**

Run: `grep -n "function.*store\|function.*update" app/Services/ProductService.php | head -20`

Identify the methods that handle product creation/update.

**Step 2: Add video validation method**

Add this method to `app/Services/ProductService.php` (typically after other validation methods):

```php
/**
 * Validate video provider and URL
 *
 * @param array $data Product data containing video_provider and video_url
 * @return array Validated data or empty array if validation fails
 * @throws \Illuminate\Validation\ValidationException
 */
private function validateVideoProvider(array $data): array
{
    $videoProvider = $data['video_provider'] ?? null;
    $videoUrl = $data['video_url'] ?? null;

    // If no provider or URL, skip validation
    if (empty($videoProvider) && empty($videoUrl)) {
        return [
            'video_provider' => null,
            'video_url' => null,
        ];
    }

    // If provider is set but no URL, that's ok (optional)
    if (!empty($videoProvider) && empty($videoUrl)) {
        return [
            'video_provider' => $videoProvider,
            'video_url' => null,
        ];
    }

    // If URL is set but no provider, error
    if (empty($videoProvider) && !empty($videoUrl)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'video_provider' => [translate('please_select_video_provider')],
        ]);
    }

    // Validate based on provider
    if ($videoProvider === 'kinescope') {
        if (!\App\Services\KinescopeService::validateUrl($videoUrl)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'video_url' => [translate('invalid_kinescope_url_format')],
            ]);
        }
    } elseif ($videoProvider === 'youtube') {
        // YouTube validation (existing logic or basic check)
        if (!str_contains($videoUrl, 'youtube.com/embed/') && !str_contains($videoUrl, 'youtu.be/')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'video_url' => [translate('invalid_youtube_url_format')],
            ]);
        }
    }

    return [
        'video_provider' => $videoProvider,
        'video_url' => $videoUrl,
    ];
}
```

**Step 3: Integrate validation into store/update methods**

Find where product data is being validated/saved. Add validation call before saving:

```php
// Add this before creating/updating product
$validatedVideo = $this->validateVideoProvider($request->all());
$data['video_provider'] = $validatedVideo['video_provider'];
$data['video_url'] = $validatedVideo['video_url'];
```

**Step 4: Write integration test**

Create `tests/Feature/Services/ProductServiceVideoTest.php`:

```php
<?php

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductServiceVideoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test saving product with Kinescope video
     */
    public function test_save_product_with_kinescope_video(): void
    {
        $this->actingAsAdmin();

        $data = $this->getBasicProductData();
        $data['video_provider'] = 'kinescope';
        $data['video_url'] = 'https://kinescope.io/abc123';

        $product = app(ProductService::class)->store($data);

        $this->assertNotNull($product);
        $this->assertEquals('kinescope', $product->video_provider);
        $this->assertEquals('https://kinescope.io/abc123', $product->video_url);
    }

    /**
     * Test saving product with invalid Kinescope URL fails
     */
    public function test_save_product_with_invalid_kinescope_url_fails(): void
    {
        $this->actingAsAdmin();
        $this->expectException(ValidationException::class);

        $data = $this->getBasicProductData();
        $data['video_provider'] = 'kinescope';
        $data['video_url'] = 'https://youtube.com/invalid';

        app(ProductService::class)->store($data);
    }

    /**
     * Test saving product with URL but no provider fails
     */
    public function test_save_product_with_url_but_no_provider_fails(): void
    {
        $this->actingAsAdmin();
        $this->expectException(ValidationException::class);

        $data = $this->getBasicProductData();
        $data['video_provider'] = null;
        $data['video_url'] = 'https://kinescope.io/abc123';

        app(ProductService::class)->store($data);
    }

    /**
     * Helper: Get basic product data
     */
    private function getBasicProductData(): array
    {
        return [
            'name' => 'Test Product',
            'category_id' => 1,
            'unit_price' => 100,
            'product_type' => 'physical',
            'unit' => 'pc',
            // Add other required fields based on your Product model
        ];
    }

    /**
     * Helper: Act as admin user
     */
    private function actingAsAdmin(): void
    {
        // Implement based on your auth setup
        // Example: $this->actingAs(User::factory()->admin()->create());
    }
}
```

**Step 5: Run test**

Run: `php artisan test tests/Feature/Services/ProductServiceVideoTest.php`

Expected: Tests may fail initially. Adjust based on your actual ProductService implementation.

**Step 6: Commit**

```bash
git add app/Services/ProductService.php tests/Feature/Services/ProductServiceVideoTest.php
git commit -m "feat: add video provider validation to ProductService

- Validate provider and URL combination
- Kinescope URL validation using KinescopeService
- YouTube URL basic validation
- Throw validation exceptions with translated messages
- Integration tests for video validation

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Task 6: Add Translation Keys

**Files:**
- Modify: `resources/lang/ru/new-messages.php`

**Step 1: Add Russian translations**

Add these keys to `resources/lang/ru/new-messages.php`:

```php
// Video Provider translations
'video_provider' => 'Видео провайдер',
'select_provider' => 'Выбрать провайдер',
'select_provider_first' => 'Сначала выберите провайдер',
'video_link' => 'Ссылка на видео',
'select_video_provider_and_add_video_link' => 'Выберите видео провайдер и добавьте ссылку на видео',
'any_kinescope_link_format' => 'Можно использовать любой формат ссылки Kinescope',
'use_embed_link_not_direct' => 'Используйте embed ссылку, не прямую',
'please_select_video_provider' => 'Пожалуйста, выберите видео провайдер',
'invalid_kinescope_url_format' => 'Неверный формат ссылки Kinescope. Используйте: kinescope.io/VIDEO_ID или kinescope.io/embed/VIDEO_ID',
'invalid_youtube_url_format' => 'Неверный формат ссылки YouTube. Используйте embed ссылку: youtube.com/embed/VIDEO_ID',
```

**Step 2: Verify translations work**

1. Change app locale to Russian (should be default)
2. Navigate to product add form
3. Verify all labels and hints display in Russian

**Step 3: Commit**

```bash
git add resources/lang/ru/new-messages.php
git commit -m "feat: add Russian translations for video provider

- Translation keys for provider selection
- Kinescope and YouTube hints
- Validation error messages
- Form labels and placeholders

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Task 7: Update Default Theme Product Details Page

**Files:**
- Modify: `resources/themes/default/web-views/products/details.blade.php`

**Step 1: Find current video display section**

Run: `grep -n "video_url" resources/themes/default/web-views/products/details.blade.php`

This will show where YouTube video is currently displayed.

**Step 2: Update video display logic**

Find the video section (around line 480-500) and replace with:

```blade
{{-- Product Video Section --}}
@if($product->video_provider && $product->video_url)
    <div class="product-video mb-4">
        @if($product->video_provider === 'kinescope')
            @php
                $videoId = \App\Services\KinescopeService::parseVideoUrl($product->video_url);
            @endphp

            @if($videoId)
                <h5 class="mb-3">{{ translate('product_video') }}</h5>
                <div class="ratio ratio-16x9">
                    <iframe
                        src="{{ \App\Services\KinescopeService::getEmbedUrl($videoId) }}"
                        frameborder="0"
                        allow="autoplay; fullscreen; picture-in-picture; encrypted-media; gyroscope; accelerometer"
                        allowfullscreen
                        loading="lazy">
                    </iframe>
                </div>
            @endif

        @elseif($product->video_provider === 'youtube' && str_contains($product->video_url, 'youtube.com/embed/'))
            <h5 class="mb-3">{{ translate('product_video') }}</h5>
            <div class="ratio ratio-16x9">
                <iframe
                    src="{{ $product->video_url }}"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                </iframe>
            </div>
        @endif
    </div>
@endif
```

**Step 3: Add CSS for video styling (if needed)**

Check if `ratio` class exists. If not, add to theme CSS or inline:

```blade
@push('css')
<style>
.product-video {
    margin-bottom: 2rem;
}

.product-video .ratio {
    position: relative;
    width: 100%;
}

.product-video .ratio::before {
    display: block;
    padding-top: 56.25%; /* 16:9 aspect ratio */
    content: "";
}

.product-video .ratio > iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

@media (max-width: 576px) {
    .product-video {
        margin-bottom: 1.5rem;
    }
}
</style>
@endpush
```

**Step 4: Test video display**

1. Create a product with Kinescope video
2. View product page
3. Verify video player appears above description
4. Test responsive behavior on mobile
5. Verify fullscreen works
6. Create product with YouTube video
7. Verify YouTube still works

**Step 5: Commit**

```bash
git add resources/themes/default/web-views/products/details.blade.php
git commit -m "feat: display Kinescope videos on product page

- Show Kinescope player above product description
- Parse video ID using KinescopeService
- Maintain YouTube video support
- Responsive 16:9 aspect ratio
- Lazy loading for performance

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Task 8: Update Theme Aster Product Details Page

**Files:**
- Modify: `resources/themes/theme_aster/theme-views/product/details.blade.php`

**Step 1: Find video section in theme_aster**

Run: `grep -n "video_url" resources/themes/theme_aster/theme-views/product/details.blade.php`

**Step 2: Apply same video display logic**

Find the video section and replace with the same code from Task 7, Step 2:

```blade
{{-- Product Video Section --}}
@if($product->video_provider && $product->video_url)
    <div class="product-video mb-4">
        @if($product->video_provider === 'kinescope')
            @php
                $videoId = \App\Services\KinescopeService::parseVideoUrl($product->video_url);
            @endphp

            @if($videoId)
                <h5 class="mb-3">{{ translate('product_video') }}</h5>
                <div class="ratio ratio-16x9">
                    <iframe
                        src="{{ \App\Services\KinescopeService::getEmbedUrl($videoId) }}"
                        frameborder="0"
                        allow="autoplay; fullscreen; picture-in-picture; encrypted-media; gyroscope; accelerometer"
                        allowfullscreen
                        loading="lazy">
                    </iframe>
                </div>
            @endif

        @elseif($product->video_provider === 'youtube' && str_contains($product->video_url, 'youtube.com/embed/'))
            <h5 class="mb-3">{{ translate('product_video') }}</h5>
            <div class="ratio ratio-16x9">
                <iframe
                    src="{{ $product->video_url }}"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                </iframe>
            </div>
        @endif
    </div>
@endif
```

**Step 3: Add CSS for theme_aster (if needed)**

Check theme_aster CSS and add similar responsive styles if needed.

**Step 4: Test on theme_aster**

1. Switch theme to theme_aster in settings
2. View product with Kinescope video
3. Verify video displays correctly
4. Test responsive behavior

**Step 5: Commit**

```bash
git add resources/themes/theme_aster/theme-views/product/details.blade.php
git commit -m "feat: display Kinescope videos on theme_aster

- Same video display logic as default theme
- Support Kinescope and YouTube
- Responsive 16:9 aspect ratio
- Consistent behavior across themes

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Task 9: Manual End-to-End Testing

**Testing Checklist:**

**Admin Panel - Add Product:**
- [ ] Navigate to admin product add page
- [ ] Video provider dropdown displays: "Выбрать провайдер", "YouTube", "Kinescope"
- [ ] URL input disabled by default
- [ ] Select YouTube → input enables, shows YouTube placeholder
- [ ] Select Kinescope → input enables, shows Kinescope placeholder
- [ ] Enter invalid URL → validation error on save
- [ ] Enter valid Kinescope embed URL → saves successfully
- [ ] Enter valid Kinescope play URL → saves successfully
- [ ] Enter valid Kinescope video ID only → saves successfully

**Admin Panel - Edit Product:**
- [ ] Edit product with YouTube video → YouTube pre-selected, URL populated
- [ ] Edit product with Kinescope video → Kinescope pre-selected, URL populated
- [ ] Change provider → confirmation prompt shows
- [ ] Accept change → URL clears, new provider selected
- [ ] Decline change → provider reverts to original

**Vendor Panel:**
- [ ] Login as vendor
- [ ] Test same scenarios as admin panel (add/edit)
- [ ] Verify vendor can add Kinescope videos to their products

**Frontend - Default Theme:**
- [ ] View product with Kinescope video
- [ ] Video player displays above description
- [ ] Player aspect ratio is 16:9
- [ ] Fullscreen button works
- [ ] Video plays correctly
- [ ] Responsive on mobile (320px, 768px, 1024px widths)
- [ ] View product with YouTube video → still works

**Frontend - Theme Aster:**
- [ ] Switch to theme_aster
- [ ] Test same frontend scenarios
- [ ] Verify consistent behavior

**Edge Cases:**
- [ ] Product with provider but no URL → saves, no video shows
- [ ] Product with no provider and no URL → saves normally
- [ ] Product with URL but no provider → validation error
- [ ] Very long video ID → handles correctly
- [ ] Special characters in URL → parsed correctly

**Step: Document any issues found**

Create file `docs/testing/kinescope-integration-test-report.md` with findings.

**Step: Fix any critical issues**

If critical bugs found during testing, fix them before final commit.

**Step: Commit test report**

```bash
git add docs/testing/kinescope-integration-test-report.md
git commit -m "test: add manual testing report for Kinescope integration

- Comprehensive test checklist
- Admin/vendor panel testing
- Frontend testing (both themes)
- Edge case testing
- Document any issues found

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Task 10: Update Documentation

**Files:**
- Create: `docs/features/kinescope-video-integration.md`
- Modify: `README.md` (if applicable)

**Step 1: Create feature documentation**

Create `docs/features/kinescope-video-integration.md`:

```markdown
# Kinescope Video Integration

## Overview

ManySales supports Kinescope video hosting alongside YouTube, allowing admins and vendors to add video content to product pages.

## Features

- **Dual Provider Support**: Choose between YouTube and Kinescope for each product
- **Flexible URL Format**: Kinescope accepts embed, play, or ID-only formats
- **Admin & Vendor Support**: Both user types can add videos
- **Responsive Player**: 16:9 aspect ratio, works on all devices
- **Automatic Parsing**: System extracts video ID from any valid format

## Supported Kinescope URL Formats

1. **Embed URL**: `https://kinescope.io/embed/VIDEO_ID`
2. **Play URL**: `https://kinescope.io/VIDEO_ID`
3. **Video ID Only**: `VIDEO_ID`
4. **With www prefix**: `https://www.kinescope.io/VIDEO_ID`
5. **With trailing slash**: `https://kinescope.io/VIDEO_ID/`

## How to Add Kinescope Video to Product

### Admin Panel

1. Navigate to **Products → Add New Product**
2. Scroll to **Product Video** section
3. Select **Kinescope** from provider dropdown
4. Enter Kinescope video URL (any format)
5. Save product

### Vendor Panel

Same steps as admin panel.

## Technical Details

### Backend

**Service**: `App\Services\KinescopeService`

Methods:
- `parseVideoUrl(string $url): ?string` - Extract video ID from URL
- `getEmbedUrl(string $videoId): string` - Generate embed URL
- `validateUrl(string $url): bool` - Validate URL format

**Validation**: `App\Services\ProductService::validateVideoProvider()`

### Frontend

**Display Location**: Above product description

**Themes Supported**:
- `default` - Standard theme
- `theme_aster` - Alternative theme

**Player Features**:
- Autoplay disabled by default
- Fullscreen enabled
- Lazy loading for performance
- Responsive 16:9 aspect ratio

### Database

**Table**: `products`

**Fields**:
- `video_provider` VARCHAR - 'youtube' or 'kinescope'
- `video_url` TEXT - Original URL entered by user

## Troubleshooting

### Video Not Displaying

**Check**:
1. Product has `video_provider` set to 'kinescope'
2. Product has valid `video_url`
3. URL can be parsed (check logs for parse failures)
4. Browser console for JavaScript errors

### Invalid URL Error

**Solution**:
- Verify URL is from kinescope.io domain
- Try different format (embed, play, or ID only)
- Check for typos in URL

### Player Not Responsive

**Check**:
- CSS class `.ratio-16x9` is defined
- No conflicting CSS overrides
- Browser supports CSS aspect ratio

## API Reference

### KinescopeService::parseVideoUrl()

```php
/**
 * Parse Kinescope URL and extract video ID
 *
 * @param string $url The URL or video ID
 * @return string|null Video ID or null if invalid
 */
public static function parseVideoUrl(string $url): ?string
```

**Examples**:

```php
KinescopeService::parseVideoUrl('https://kinescope.io/embed/abc123');
// Returns: 'abc123'

KinescopeService::parseVideoUrl('https://kinescope.io/abc123');
// Returns: 'abc123'

KinescopeService::parseVideoUrl('abc123');
// Returns: 'abc123'

KinescopeService::parseVideoUrl('https://youtube.com/watch?v=xyz');
// Returns: null
```

### KinescopeService::getEmbedUrl()

```php
/**
 * Generate embed URL from video ID
 *
 * @param string $videoId The video ID
 * @return string The embed URL
 */
public static function getEmbedUrl(string $videoId): string
```

**Example**:

```php
KinescopeService::getEmbedUrl('abc123');
// Returns: 'https://kinescope.io/embed/abc123'
```

### KinescopeService::validateUrl()

```php
/**
 * Validate if URL is valid Kinescope URL
 *
 * @param string $url The URL to validate
 * @return bool True if valid
 */
public static function validateUrl(string $url): bool
```

**Example**:

```php
KinescopeService::validateUrl('https://kinescope.io/abc123');
// Returns: true

KinescopeService::validateUrl('invalid');
// Returns: false
```

## Testing

### Unit Tests

Run: `php artisan test tests/Unit/Services/KinescopeServiceTest.php`

Coverage:
- URL parsing (all formats)
- Embed URL generation
- URL validation
- Edge cases

### Integration Tests

Run: `php artisan test tests/Feature/Services/ProductServiceVideoTest.php`

Coverage:
- Product creation with Kinescope video
- Validation errors
- Provider/URL combinations

### Manual Testing

See: `docs/testing/kinescope-integration-test-report.md`

## Future Enhancements

Potential improvements:
- Support for Kinescope playlists
- Video thumbnail preview in admin
- Upload videos directly to Kinescope via API
- Analytics integration
- Multiple videos per product

## References

- Design Document: `docs/plans/2025-12-12-kinescope-integration-design.md`
- Implementation Plan: `docs/plans/2025-12-12-kinescope-implementation.md`
- Kinescope API: https://kinescope.io
```

**Step 2: Commit documentation**

```bash
git add docs/features/kinescope-video-integration.md
git commit -m "docs: add Kinescope video integration documentation

- Feature overview and usage guide
- Admin and vendor instructions
- Technical implementation details
- API reference with examples
- Troubleshooting guide
- Testing information

Refs: docs/plans/2025-12-12-kinescope-integration-design.md"
```

---

## Final Verification Checklist

Before considering implementation complete, verify:

- [ ] All unit tests pass: `php artisan test tests/Unit/Services/KinescopeServiceTest.php`
- [ ] All integration tests pass: `php artisan test tests/Feature/Services/ProductServiceVideoTest.php`
- [ ] Manual testing completed (see Task 9)
- [ ] All files committed
- [ ] No debug code left in (console.log, dd(), dump(), etc.)
- [ ] Translations complete for Russian
- [ ] Both themes tested
- [ ] Documentation complete
- [ ] Design document referenced in commits
- [ ] Git history is clean with descriptive commits

## Rollback Plan

If critical issues found in production:

1. **Quick Disable**: Set all products `video_provider = NULL` where `video_provider = 'kinescope'`
2. **Code Rollback**: Revert commits related to Kinescope integration
3. **Database**: No rollback needed (fields already existed)

## Notes

- **DRY**: JavaScript handler reused across all forms
- **YAGNI**: No API integration, no upload feature - only what's needed
- **TDD**: Tests written before implementation
- **Commits**: Frequent, focused commits with clear messages
- **Existing YouTube**: Preserved and working alongside Kinescope

---

**Implementation Time Estimate**: 4-6 hours for experienced developer

**Priority**: Medium

**Risk Level**: Low (no database changes, additive feature)
