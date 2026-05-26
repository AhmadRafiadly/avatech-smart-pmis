<?php

/*
 * AI configuration. AiPlanner resolves configured providers in fallback
 * order and keeps API keys in environment variables only.
 *
 * Keep the api_key resolution as plain env() reads so nothing leaks into
 * config cache as a hardcoded literal. If no provider key is set,
 * App\Services\AiPlanner::isConfigured() returns false.
 */
/*
 * Expected env keys:
 * AI_PROVIDER_ORDER=gemini,groq,openrouter
 * GEMINI_API_KEY, GEMINI_MODEL
 * GROQ_API_KEY, GROQ_MODEL
 * OPENROUTER_API_KEY, OPENROUTER_MODEL
 * AI_TIMEOUT_SECONDS
 */
$providerOrder = array_values(array_filter(array_map(
    static fn ($provider) => strtolower(trim($provider)),
    explode(',', (string) env('AI_PROVIDER_ORDER', 'gemini,groq,openrouter')),
)));

return [
    'provider_order' => $providerOrder ?: ['gemini', 'groq', 'openrouter'],
    'timeout_seconds' => max(1, (int) env('AI_TIMEOUT_SECONDS', 30)),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model'   => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
    ],
];
