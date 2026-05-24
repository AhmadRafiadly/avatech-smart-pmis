<?php

/*
 * AI configuration. Phase 3A: readiness only — no provider calls are made
 * anywhere in the app yet. Real generation will be wired in a later pass.
 *
 * Keep the api_key resolution as plain env() reads so nothing leaks into
 * config cache as a hardcoded literal. If GEMINI_API_KEY is not set,
 * App\Services\AiPlanner::isConfigured() returns false and the UI shows
 * an "AI belum dikonfigurasi" gate.
 */
return [
    'provider' => env('AI_PROVIDER', 'gemini'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    ],
];
