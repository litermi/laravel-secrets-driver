<?php

use Illuminate\Support\Str;

/**
 * Configuration file for the Secrets Driver
 * 
 * @author Carlos González (carlos dot gonzalez at litermi dot com)
 */
return [
    'cache-interval' => [
        "regular" => env("SECRETS_DRIVER_CACHE_INTERVAL", "30s"),
        "backup" => env("SECRETS_DRIVER_CACHE_BACKUP_INTERVAL", "12h"),
        "notification" => env("SECRETS_DRIVER_CACHE_NOTIFICATION_INTERVAL", "10s"),
    ],
    'cache-key-prefix' => [
        "regular" => env("SECRETS_DRIVER_CACHE_KEY_PREFIX", "secret-data"),
        "notification" => env("SECRETS_DRIVER_NOTIFICATION_CACHE_KEY_PREFIX", "notification-sent-secret"),
    ],
    'severity-level' => env("SECRETS_DRIVER_SEVERITY_LEVEL", "critical"),
    'project-tag' => env("SECRETS_DRIVER_PROJECT_TAG", Str::slug(env('APP_NAME', 'litermi-project'))),
    'manager' => env("SECRETS_DRIVER_MANAGER", "aws"),
    'secret-name-format' => env("SECRETS_DRIVER_NAME_FORMAT", '$env/$project/$key'),
    'production-tag' => env("SECRETS_DRIVER_PRODUCTION_TAG", "prod"),
];