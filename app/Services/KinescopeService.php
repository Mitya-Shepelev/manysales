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
