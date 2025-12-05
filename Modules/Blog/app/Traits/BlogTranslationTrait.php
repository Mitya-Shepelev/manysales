<?php

namespace Modules\Blog\app\Traits;

use Modules\Blog\app\Models\BlogTranslation;

trait BlogTranslationTrait
{
    public function addBlogTranslation(object $request, int|string $id): bool
    {
        foreach ($request->lang as $index => $key) {
            foreach (['name', 'description', 'title'] as $type) {
                // Skip first language (index 0) - it's stored in main model fields
                if (isset($request[$type][$key]) && $index !== 0) {
                    BlogTranslation::insert([
                        'translation_type' => 'Modules\Blog\app\Models\Blog',
                        'translation_id' => $id,
                        'locale' => $key,
                        'key' => $type,
                        'value' => $request[$type][$key],
                        'is_draft' => $request['is_draft'] ?? 0
                    ]);
                }
            }
        }
        return true;
    }

    public function updateBlogTranslation(object $request, int|string $id): bool
    {
        foreach ($request->lang as $index => $key) {
            foreach (['name', 'description', 'title'] as $type) {
                // Skip first language (index 0) - it's stored in main model fields
                if (isset($request[$type][$key]) && $index !== 0) {
                    BlogTranslation::updateOrInsert(
                        [
                            'translation_type' => 'Modules\Blog\app\Models\Blog',
                            'translation_id' => $id,
                            'locale' => $key,
                            'key' => $type,
                            'is_draft' => $request['is_draft'] ?? 0,
                        ],
                        [
                            'value' => $request[$type][$key],
                            'translation_type' => 'Modules\Blog\app\Models\Blog',
                            'translation_id' => $id,
                            'locale' => $key,
                            'key' => $type,
                            'is_draft' => $request['is_draft'] ?? 0,
                        ]
                    );
                }
            }
        }
        return true;
    }

    public function updateTranslationById(string $id, string $lang, string $key, string $value): bool
    {
        BlogTranslation::updateOrInsert([
                'translation_type' => 'Modules\Blog\app\Models\Blog',
                'translation_id' => $id,
                'locale' => $lang,
                'key' => $key
            ], [
                'value' => $value,
                'is_draft' => $request['is_draft'] ?? 0
            ]);
        return true;
    }

    public function deleteTranslation(int|string $id): bool
    {
        BlogTranslation::where([
            'translation_type' => 'Modules\Blog\app\Models\Blog',
            'translation_id' => $id
        ])->delete();
        return true;
    }
}
