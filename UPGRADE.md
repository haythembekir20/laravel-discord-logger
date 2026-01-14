# Upgrade Guide

## Upgrading from 1.x to 2.x

### Service Provider Auto-Discovery

The service provider namespace has changed. If you have manually registered the provider in `config/app.php`, update the reference:

```php
// Before (1.x)
'providers' => [
    HaythemBekir\DiscordLogger\DiscordLoggerServiceProvider::class,
],

// After (2.x)
'providers' => [
    HaythemBekir\DiscordLogger\Providers\DiscordLoggerServiceProvider::class,
],
```

For most users using Laravel's auto-discovery, no changes are needed.

### Extending the Package

If you extended `DiscordNotificationService` in 1.x, you'll need to migrate to the new architecture.

#### Custom Transport Implementation

Create a custom transport by implementing `DiscordTransport`:

```php
use HaythemBekir\DiscordLogger\Domain\Contracts\DiscordTransport;
use HaythemBekir\DiscordLogger\Domain\ValueObjects\DiscordMessage;

class MyCustomTransport implements DiscordTransport
{
    public function send(DiscordMessage $message): void
    {
        // Your custom logic here
    }
}
```

Register in your service provider:

```php
$this->app->bind(DiscordTransport::class, MyCustomTransport::class);
```

#### Custom Rate Limiting

Implement `RateLimiter` interface for custom rate limiting:

```php
use HaythemBekir\DiscordLogger\Domain\Contracts\RateLimiter;

class DatabaseRateLimiter implements RateLimiter
{
    public function attempt(string $key, int $maxAttempts): bool
    {
        // Your logic
    }

    public function remaining(string $key, int $maxAttempts): int
    {
        // Your logic
    }

    public function clear(string $key): void
    {
        // Your logic
    }
}
```

### Configuration

The configuration file structure remains the same. No changes needed.

### Queue Jobs

If you directly instantiated `SendDiscordNotification` job, update to use `DiscordMessage`:

```php
// Before (1.x)
dispatch(new SendDiscordNotification($level, $message, $context, $config));

// After (2.x)
use HaythemBekir\DiscordLogger\Domain\ValueObjects\DiscordMessage;
use HaythemBekir\DiscordLogger\Infrastructure\Queue\SendDiscordNotificationJob;

$discordMessage = DiscordMessage::forLogAlert(...);
dispatch(new SendDiscordNotificationJob($discordMessage));
```

### Using Actions Directly

You can now use actions directly in your code:

```php
use HaythemBekir\DiscordLogger\Application\Realtime\SendLogNotificationAction;
use HaythemBekir\DiscordLogger\Application\Realtime\LogNotificationDTO;
use HaythemBekir\DiscordLogger\Domain\ValueObjects\LogLevel;
use HaythemBekir\DiscordLogger\Domain\ValueObjects\LogContext;

class YourService
{
    public function __construct(
        private SendLogNotificationAction $sendNotification,
    ) {}

    public function doSomething(): void
    {
        $dto = new LogNotificationDTO(
            level: LogLevel::Error,
            message: 'Something went wrong',
            context: LogContext::fromArray(['key' => 'value']),
        );

        $this->sendNotification->execute($dto);
    }
}
```

### Logging Configuration

The custom log driver configuration remains the same:

```php
// config/logging.php
'discord' => [
    'driver' => 'custom',
    'via' => \HaythemBekir\DiscordLogger\Infrastructure\Logging\CreateDiscordLogger::class,
    'level' => 'error',
],
```

Note the namespace change for `CreateDiscordLogger`.
