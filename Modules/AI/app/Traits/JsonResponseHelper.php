<?php

namespace Modules\AI\app\Traits;

trait JsonResponseHelper
{
    /**
     * Extract clean JSON from AI response that might be wrapped in markdown code blocks
     */
    protected function extractJsonFromResponse(string $response): string
    {
        $response = trim($response);

        // Remove markdown code blocks (```json ... ``` or ``` ... ```)
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
            return trim($matches[1]);
        }

        // If no code blocks, return trimmed response
        return $response;
    }

    /**
     * Safely decode JSON from AI response, handling markdown code blocks
     */
    protected function safeJsonDecode(string $response): mixed
    {
        $cleanJson = $this->extractJsonFromResponse($response);
        return json_decode($cleanJson, true);
    }
}
