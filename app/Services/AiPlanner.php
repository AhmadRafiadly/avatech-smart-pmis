<?php

namespace App\Services;

/**
 * Phase 3A skeleton. Provides a readiness check the UI can use to decide
 * between "AI belum dikonfigurasi" and "Siap generate" copy. No network
 * calls happen here yet — generation will be wired in a later phase.
 */
class AiPlanner
{
    /**
     * True when an API key is present for the active provider. Currently
     * only Gemini is wired into config; extend the match below if another
     * provider is added later.
     */
    public static function isConfigured(): bool
    {
        $provider = (string) (config('ai.provider') ?: 'gemini');

        $key = match ($provider) {
            'gemini' => config('ai.gemini.api_key'),
            default  => null,
        };

        return is_string($key) && trim($key) !== '';
    }

    /**
     * Human-readable provider label for UI tooltips.
     */
    public static function providerLabel(): string
    {
        return match ((string) (config('ai.provider') ?: 'gemini')) {
            'gemini' => 'Gemini',
            default  => 'AI',
        };
    }
}
