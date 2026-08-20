<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI driver
    |--------------------------------------------------------------------------
    |
    | Which provider the AI Service Layer talks to. The 'local' driver is a
    | fully offline, data-grounded heuristic engine that needs no API key and
    | always works — it is the default so the platform's AI is demo-ready out
    | of the box. Set AI_DRIVER=anthropic (or openai) and drop in a key to
    | switch to a real large language model without touching any other code.
    |
    | Supported: "local", "anthropic", "openai"
    |
    */

    'driver' => env('AI_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Product identity
    |--------------------------------------------------------------------------
    */

    'assistant_name' => env('AI_ASSISTANT_NAME', 'OSEP AI'),

    /*
     * Role-specific copilot names. Each workspace (planner, vendor, client) gets
     * its own assistant identity so the AI reads as purpose-built for that role.
     */
    'vendor_assistant_name' => env('AI_VENDOR_ASSISTANT_NAME', 'OSEP Vendor Copilot'),
    'client_assistant_name' => env('AI_CLIENT_ASSISTANT_NAME', 'OSEP Planning Concierge'),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [

        'anthropic' => [
            'key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'version' => '2023-06-01',
            'max_tokens' => (int) env('AI_MAX_TOKENS', 1200),
        ],

        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'max_tokens' => (int) env('AI_MAX_TOKENS', 1200),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Request timeout (seconds) for real-LLM HTTP calls
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('AI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Recommendation / health-score freshness
    |--------------------------------------------------------------------------
    |
    | How long (minutes) generated recommendations and health scores are
    | considered fresh before the engine recomputes them on the next request.
    |
    */

    'freshness_minutes' => (int) env('AI_FRESHNESS_MINUTES', 30),

];
