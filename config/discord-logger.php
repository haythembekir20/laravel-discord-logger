<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Discord Logger Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Discord webhook notifications.
    | Real-time notifications and daily reports can be configured separately.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Real-time Error Notifications
    |--------------------------------------------------------------------------
    |
    | Send errors to Discord instantly as they happen.
    | Each setting can be configured independently.
    |
    */

    'realtime' => [
        'enabled' => env('DISCORD_LOGGER_REALTIME_ENABLED', false),
        'webhook_url' => env('DISCORD_LOGGER_REALTIME_WEBHOOK_URL'),

        // Use queue for zero performance impact (recommended)
        'async' => env('DISCORD_LOGGER_REALTIME_ASYNC', true),

        // Log levels to notify: emergency, alert, critical, error, warning, notice, info, debug
        'notify_levels' => explode(',', env('DISCORD_LOGGER_REALTIME_LEVELS', 'emergency,alert,critical,error')),

        // Rate limiting to prevent flooding
        'rate_limit' => [
            'enabled' => env('DISCORD_LOGGER_RATE_LIMIT_ENABLED', true),
            'max_per_minute' => env('DISCORD_LOGGER_RATE_LIMIT_MAX', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Daily Report (Cron)
    |--------------------------------------------------------------------------
    |
    | Send daily log statistics report at a scheduled time.
    | Requires scheduler to be running: * * * * * php artisan schedule:run
    |
    */

    'daily_report' => [
        'enabled' => env('DISCORD_LOGGER_DAILY_REPORT_ENABLED', false),
        'webhook_url' => env('DISCORD_LOGGER_DAILY_REPORT_WEBHOOK_URL'),

        // Time to send the report (24h format)
        'time' => env('DISCORD_LOGGER_DAILY_REPORT_TIME', '08:00'),
        'timezone' => env('DISCORD_LOGGER_DAILY_REPORT_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Appearance
    |--------------------------------------------------------------------------
    |
    | Customize the appearance of Discord messages (shared by both).
    |
    */

    'appearance' => [
        'username' => env('DISCORD_LOGGER_BOT_USERNAME', 'Laravel Logger'),
        'avatar_url' => env('DISCORD_LOGGER_BOT_AVATAR_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embed Colors by Level
    |--------------------------------------------------------------------------
    |
    | Discord embed colors for each log level (in decimal format).
    |
    */

    'colors' => [
        'emergency' => 0xFF0000, // Red
        'alert'     => 0xFF3300, // Orange-Red
        'critical'  => 0xFF6600, // Orange
        'error'     => 0xFF9900, // Dark Orange
        'warning'   => 0xFFCC00, // Yellow
        'notice'    => 0x00CCFF, // Cyan
        'info'      => 0x0099FF, // Blue
        'debug'     => 0x999999, // Gray
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Label
    |--------------------------------------------------------------------------
    |
    | Label to identify which environment the logs are coming from.
    |
    */

    'environment_label' => env('DISCORD_LOGGER_ENVIRONMENT_LABEL', env('APP_ENV', 'production')),

];
