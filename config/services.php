<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        // Legacy second model, used only when no chain list below is set.
        'fallback_model' => env('GEMINI_FALLBACK_MODEL', ''),

        // Ordered free-tier model chains, best first (see App\Services\Ai\
        // GeminiClient::generateJson() and AiModelChain). Comma separated; an
        // entry may carry a thinking level after a colon
        // ("gemini-3.5-flash:minimal"). "judge" = grading a learner's answer
        // (Evaluator); "generate" = review practices (ReviewPracticeGenerator).
        // Quota is counted per model, so keep the first entries of the two
        // lists different: generation traffic must not drain the grader's
        // allowance. Empty = the legacy model + fallback_model pair above.
        'judge_models' => array_values(array_filter(array_map('trim', explode(',', (string) env('GEMINI_JUDGE_MODELS', ''))))),
        'generate_models' => array_values(array_filter(array_map('trim', explode(',', (string) env('GEMINI_GENERATE_MODELS', ''))))),

        // So a learner is never left waiting on a model that is down: each
        // attempt is cut off after attempt_timeout seconds (the relay caps a
        // provider call at 30 s anyway), a request tries at most
        // max_attempts models, and total_budget seconds is the ceiling
        // across all of them.
        'attempt_timeout' => (int) env('GEMINI_ATTEMPT_TIMEOUT', 10),
        'max_attempts' => (int) env('GEMINI_MAX_ATTEMPTS', 3),
        'total_budget' => (int) env('GEMINI_TOTAL_BUDGET', 20),
    ],

    'ai_proxy' => [
        'url' => env('AI_PROXY_URL'),
        'secret' => env('AI_PROXY_SECRET'),
    ],

];
