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
