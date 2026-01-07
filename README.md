# Laravel Discord Logger

A Laravel package for sending log notifications to Discord via webhooks. Supports real-time error alerts and daily log reports.

## Features

- **Real-time Notifications**: Send log messages to Discord instantly as they happen
- **Daily Reports**: Scheduled summary of log statistics with top recurring errors
- **Async Support**: Queue-based notifications for zero performance impact
- **Rate Limiting**: Prevent webhook flooding during error storms
- **Customizable**: Configure log levels, colors, bot appearance, and more
- **Environment Labels**: Identify which environment logs are coming from

## Requirements

- PHP 8.1+
- Laravel 10.x or 11.x

## Installation

Install via Composer:

```bash
composer require haythem-bekir/laravel-discord-logger
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=discord-logger-config
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Real-time Notifications
DISCORD_LOGGER_REALTIME_ENABLED=true
DISCORD_LOGGER_REALTIME_WEBHOOK_URL=https://discord.com/api/webhooks/your-webhook-url
DISCORD_LOGGER_REALTIME_ASYNC=true
DISCORD_LOGGER_REALTIME_LEVELS=emergency,alert,critical,error

# Rate Limiting
DISCORD_LOGGER_RATE_LIMIT_ENABLED=true
DISCORD_LOGGER_RATE_LIMIT_MAX=10

# Daily Reports
DISCORD_LOGGER_DAILY_REPORT_ENABLED=true
DISCORD_LOGGER_DAILY_REPORT_WEBHOOK_URL=https://discord.com/api/webhooks/your-webhook-url
DISCORD_LOGGER_DAILY_REPORT_TIME=08:00
DISCORD_LOGGER_DAILY_REPORT_TIMEZONE=UTC

# Appearance
DISCORD_LOGGER_BOT_USERNAME="Laravel Logger"
DISCORD_LOGGER_BOT_AVATAR_URL=

# Environment Label
DISCORD_LOGGER_ENVIRONMENT_LABEL=production
```

### Setting Up the Log Channel

Add the Discord channel to your `config/logging.php`:

```php
'channels' => [
    // ... other channels

    'discord' => [
        'driver' => 'custom',
        'via' => \HaythemBekir\DiscordLogger\Logging\CreateDiscordLogger::class,
        'level' => 'error',
    ],
],
```

### Using with Stack Channel

To send logs to both file and Discord:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'discord'],
        'ignore_exceptions' => false,
    ],

    // ... other channels
],
```

## Usage

### Real-time Notifications

Once configured, any log message at or above the configured level will be sent to Discord:

```php
// These will trigger Discord notifications (if level is 'error' or above)
Log::error('Something went wrong!');
Log::critical('Database connection failed', ['host' => 'localhost']);

// This won't trigger (below error level by default)
Log::warning('This is just a warning');
```

### Daily Reports

Run the daily report command manually:

```bash
# Send yesterday's report
php artisan discord-logger:daily-report

# Send report for a specific date
php artisan discord-logger:daily-report --date=2024-01-15

# Preview without sending (dry run)
php artisan discord-logger:daily-report --dry-run
```

#### Scheduling the Daily Report

To automatically send daily reports, add this to your `routes/console.php` (Laravel 11+) or `app/Console/Kernel.php`:

**Laravel 11+ (`routes/console.php`):**

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('discord-logger:daily-report')
    ->dailyAt('08:00')
    ->timezone('UTC');
```

**Laravel 10 (`app/Console/Kernel.php`):**

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('discord-logger:daily-report')
        ->dailyAt('08:00')
        ->timezone('UTC');
}
```

Make sure your scheduler is running:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Direct Service Usage

You can also use the service directly:

```php
use HaythemBekir\DiscordLogger\Services\DiscordNotificationService;

$discord = app(DiscordNotificationService::class);

// Send a custom notification
$discord->sendLogNotification('error', 'Custom error message', [
    'user_id' => 123,
    'action' => 'login_failed',
]);

// Send a custom report
$discord->sendDailyReport([
    'date' => '2024-01-15',
    'total' => 150,
    'by_level' => [
        'error' => 10,
        'warning' => 40,
        'info' => 100,
    ],
]);
```

## Discord Webhook Setup

1. Go to your Discord server settings
2. Navigate to **Integrations** > **Webhooks**
3. Click **New Webhook**
4. Choose the channel for notifications
5. Copy the webhook URL
6. Paste it in your `.env` file

## Embed Colors

Default colors for each log level (customizable in config):

| Level | Color |
|-------|-------|
| Emergency | Red |
| Alert | Orange-Red |
| Critical | Orange |
| Error | Dark Orange |
| Warning | Yellow |
| Notice | Cyan |
| Info | Blue |
| Debug | Gray |

## License

MIT License. See [LICENSE](LICENSE) for more information.
