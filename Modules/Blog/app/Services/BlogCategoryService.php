<?php

namespace Modules\Blog\app\Services;

use Illuminate\Support\Str;

class BlogCategoryService
{

    public function getAddData(object|array $request): array
    {
        $languages = getWebConfig(name: 'pnc_language') ?? ['en'];
        $baseLanguage = $languages[0];
        return [
            'name' => $request['name'][$baseLanguage],
            'slug' => $this->getCategorySlug(request: $request),
            'status' => 1,
            'click_count' => 0,
        ];
    }

    public function getUpdateData(object|array $request): array
    {
        $languages = getWebConfig(name: 'pnc_language') ?? ['en'];
        $baseLanguage = $languages[0];
        return [
            'name' => $request['name'][$baseLanguage],
            'slug' => $this->getCategorySlug(request: $request),
        ];
    }

    public function getCategorySlug(object $request): string
    {
        $languages = getWebConfig(name: 'pnc_language') ?? ['en'];
        return Str::slug($request['name'][$languages[0]], '-') . '-' . Str::random(6);
    }

    public function getCategoryLanguageData(object|array $category): array
    {
        $languages = getWebConfig(name: 'pnc_language') ?? [];
        $baseLanguage = $languages[0] ?? 'en';
        $categoryLang = [];
        foreach ($languages as $language) {
            $value = '';

            foreach ($category?->translations as $translation) {
                if ($translation->locale === $language) {
                    $value = $translation->value;
                    break;
                }
            }

            $categoryLang[] = [
                'locale' => $language,
                // First language (base) - use main model field, others - use translation
                'value' => $language === $baseLanguage ? ($category->name ?? '') : $value,
            ];
        }
        return $categoryLang;
    }

    public function getCategoryDropdown(object $request, object $categories): string
    {
        $languages = getWebConfig(name: 'pnc_language') ?? ['en'];
        $baseLanguage = $languages[0];
        $dropdown = '<option value="' . 0 . '" disabled selected>' . translate("Select") . '</option>';
        foreach ($categories as $category) {
            if (getDefaultLanguage() == $baseLanguage) {
                $defaultName = $category->name;
            } else {
                $defaultName = $category?->translations()->where('key', 'name')->where('locale', getDefaultLanguage())->first()?->value ?? $category?->name;
            }
            $dropdown .= '<option value="' . $category->id . '">' . $defaultName . '</option>';
        }
        return $dropdown;
    }

}
