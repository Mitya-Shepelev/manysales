<?php

namespace App\Services;

use Devrabiul\ToastMagic\ToastMagic;
use Illuminate\Support\Facades\File;

class CustomToastMagic extends ToastMagic
{
    /**
     * Generate the HTML for the required styles using project's dynamicAsset helper.
     */
    public function styles(): string
    {
        $stylePath = 'public/packages/devrabiul/laravel-toaster-magic/css/laravel-toaster-magic.css';
        if (File::exists(public_path('packages/devrabiul/laravel-toaster-magic/css/laravel-toaster-magic.css'))) {
            return '<link rel="stylesheet" href="' . dynamicAsset($stylePath) . '">';
        }
        return '<link rel="stylesheet" href="' . dynamicAsset('vendor/devrabiul/laravel-toaster-magic/assets/css/laravel-toaster-magic.css') . '">';
    }

    /**
     * Generate the HTML for the required scripts using project's dynamicAsset helper.
     */
    public function scriptsPath(): string
    {
        $config = (array) config('laravel-toaster-magic');
        $scripts = [];

        if (!empty($config['livewire_enabled'])) {
            $file1 = 'public/packages/devrabiul/laravel-toaster-magic/js/livewire-v3/laravel-toaster-magic.js';
            $file2 = 'public/packages/devrabiul/laravel-toaster-magic/js/livewire-v3/livewire-toaster-magic-v3.js';
            if (File::exists(public_path('packages/devrabiul/laravel-toaster-magic/js/livewire-v3/laravel-toaster-magic.js')) &&
                File::exists(public_path('packages/devrabiul/laravel-toaster-magic/js/livewire-v3/livewire-toaster-magic-v3.js'))) {
                $scripts[] = '<script src="' . dynamicAsset($file1) . '"></script>';
                $scripts[] = '<script src="' . dynamicAsset($file2) . '"></script>';
            }
            return implode('', $scripts);
        }

        $defaultJsPath = 'public/packages/devrabiul/laravel-toaster-magic/js/laravel-toaster-magic.js';
        if (File::exists(public_path('packages/devrabiul/laravel-toaster-magic/js/laravel-toaster-magic.js'))) {
            return '<script src="' . dynamicAsset($defaultJsPath) . '"></script>';
        }

        return '<script src="' . dynamicAsset('vendor/devrabiul/laravel-toaster-magic/assets/js/laravel-toaster-magic.js') . '"></script>';
    }
}
