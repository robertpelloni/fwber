<?php

return [
    // Sentry DSN – leave empty to disable
    'dsn' => env('SENTRY_LARAVEL_DSN', ''),

    // Release version (e.g., git commit hash)
    'release' => env('SENTRY_RELEASE', null),

    // Environment: production, staging, local
    'environment' => env('APP_ENV', 'production'),

    // Performance tracing (0.0 to 1.0)
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    // Profiling (0.0 to 1.0)
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    // Attach stacktrace to messages
    'attach_stacktrace' => true,

    // Do not send PII by default
    'send_default_pii' => (bool) env('SENTRY_SEND_DEFAULT_PII', false),
];
