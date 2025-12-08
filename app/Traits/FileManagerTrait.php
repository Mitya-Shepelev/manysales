<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

trait FileManagerTrait
{
    /**
     * Get storage type directly from database (bypasses config cache)
     */
    private function getStorageTypeFromDB(): string
    {
        try {
            $setting = DB::table('business_settings')
                ->where('type', 'storage_connection_type')
                ->first();
            return $setting?->value ?? 'public';
        } catch (\Exception $e) {
            return 'public';
        }
    }

    /**
     * Configure S3 disk from database credentials
     */
    private function configureS3Disk(): void
    {
        try {
            $credential = DB::table('business_settings')
                ->where('type', 'storage_connection_s3_credential')
                ->first();
            if ($credential && $credential->value) {
                $s3Config = json_decode($credential->value, true);
                if (!empty($s3Config)) {
                    config(['filesystems.disks.s3' => $s3Config]);
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    /**
     * upload method working for image
     * @param string $dir
     * @param string $format
     * @param $image
     * @return string
     */
    protected function upload(string $dir, string $format, $image = null): string
    {
        $storage = $this->getStorageTypeFromDB();
        if ($storage === 's3') {
            $this->configureS3Disk();
        }

        Cache::forget("cache_all_files_for_public_storage");
        if (!is_null($image)) {
            if (!$this->checkFileExists($dir)['status']) {
                Storage::disk($storage)->makeDirectory($dir);
            }

            $isOriginalImage = in_array($image->getClientOriginalExtension(), ['gif', 'svg']);
            $visibility = $storage === 's3' ? 'public' : null;

            if ($isOriginalImage) {
                $imageName = Carbon::now()->toDateString() . "-" . uniqid() . "." . $image->getClientOriginalExtension();
                Storage::disk($storage)->put($dir . $imageName, file_get_contents($image), $visibility);
            } else {
                if (in_array(request()->ip(), ['127.0.0.1', '::1']) && !(imagetypes() & IMG_WEBP) || env('APP_DEBUG') && !(imagetypes() & IMG_WEBP)) {
                    $format = 'png';
                }
                $imageWebp = Image::make($image)->encode($format);
                $imageName = Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
                Storage::disk($storage)->put($dir . $imageName, $imageWebp, $visibility);
                $imageWebp->destroy();
            }
        } else {
            $imageName = 'def.png';
        }

        cacheRemoveByType(type: 'file_manager');
        return $imageName;
    }

    /**
     * @param string $dir
     * @param string $format
     * @param $file
     * @return string
     */
    public function fileUpload(string $dir, string $format, $file = null): string
    {
        $storage = $this->getStorageTypeFromDB();
        if ($storage === 's3') {
            $this->configureS3Disk();
        }

        Cache::forget("cache_all_files_for_public_storage");

        if (!is_null($file)) {
            $fileName = Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
            if (!$this->checkFileExists($dir)['status']) {
                Storage::disk($storage)->makeDirectory($dir);
            }
            if ($file) {
                $visibility = $storage === 's3' ? 'public' : null;
                Storage::disk($storage)->put($dir . $fileName, file_get_contents($file), $visibility);
            }
        } else {
            $fileName = 'def.png';
        }

        return $fileName;
    }

    /**
     * @param string $dir
     * @param $oldImage
     * @param string $format
     * @param $image
     * @param string $fileType image/file
     * @return string
     */
    public function update(string $dir, $oldImage, string $format, $image, string $fileType = 'image'): string
    {
        if ($this->checkFileExists(filePath: $dir . $oldImage)['status']) {
            Storage::disk($this->checkFileExists(filePath: $dir . $oldImage)['disk'])->delete($dir . $oldImage);
        }
        return $fileType == 'file' ? $this->fileUpload($dir, $format, $image) : $this->upload($dir, $format, $image);
    }

    /**
     * @param string $filePath
     * @return array
     */
    protected function  delete(string $filePath): array
    {
        if ($this->checkFileExists(filePath: $filePath)['status']) {
            Storage::disk($this->checkFileExists(filePath: $filePath)['disk'])->delete($filePath);
        }
        cacheRemoveByType(type: 'file_manager');
        Cache::forget("cache_all_files_for_public_storage");
        return [
            'success' => 1,
            'message' => translate('Removed_successfully')
        ];
    }

    public function setStorageConnectionEnvironment(): void
    {
        $storageConnectionType = getWebConfig(name: 'storage_connection_type') ?? 'public';
        Config::set('filesystems.disks.default', $storageConnectionType);
        $storageConnectionS3Credential = getWebConfig(name: 'storage_connection_s3_credential');
        if ($storageConnectionType == 's3' && !empty($storageConnectionS3Credential)) {
            Config::set('filesystems.disks.' . $storageConnectionType, $storageConnectionS3Credential);
        }
    }

    private function checkFileExists(string $filePath): array
    {
        $storageType = $this->getStorageTypeFromDB();

        // First check local storage
        if (Storage::disk('public')->exists($filePath)) {
            return [
                'status' => true,
                'disk' => 'public'
            ];
        }

        // Then check S3 if configured
        if ($storageType === 's3') {
            $this->configureS3Disk();
            try {
                if (Storage::disk('s3')->exists($filePath)) {
                    return [
                        'status' => true,
                        'disk' => 's3'
                    ];
                }
            } catch (\Exception $e) {
                // S3 check failed, continue
            }
        }

        return [
            'status' => false,
            'disk' => $storageType
        ];
    }
}
