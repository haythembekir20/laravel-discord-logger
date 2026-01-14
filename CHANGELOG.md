# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-01-13

### Added
- Complete architecture refactoring to SOLID principles
- Domain-Driven Design structure with Application, Domain, and Infrastructure layers
- Config value objects (`DiscordLoggerConfig`, `RealtimeConfig`, `DailyReportConfig`, etc.)
- Transport abstraction with `DiscordTransport` interface
- Multiple transport implementations: `HttpDiscordTransport`, `QueuedDiscordTransport`, `NullDiscordTransport`
- `RateLimiter` interface with `CacheRateLimiter` implementation
- Action classes: `SendLogNotificationAction`, `GatherLogStatisticsAction`, `BuildDailyReportAction`, `SendDailyReportAction`
- Value objects: `LogLevel`, `DiscordMessage`, `LogContext`
- DTOs: `LogNotificationDTO`, `LogStatisticsDTO`, `DailyReportDTO`
- Comprehensive test suite with Pest PHP
- PHPStan static analysis (level 6)
- Laravel Pint code style enforcement
- GitHub Actions CI/CD pipeline

### Changed
- Service provider moved to `Providers\DiscordLoggerServiceProvider`
- Monolog handler refactored to delegate to action classes
- Command refactored to thin controller pattern
- Queue job simplified to use `DiscordMessage` value object
- Logging factory refactored to use dependency injection

### Removed
- `DiscordNotificationService` (replaced by action classes)
- Direct `config()` calls throughout codebase (replaced by injected config objects)
- Business logic from console command
- Business logic from Monolog handler

### Breaking Changes
- Namespace change: Service provider moved from `HaythemBekir\DiscordLogger\DiscordLoggerServiceProvider` to `HaythemBekir\DiscordLogger\Providers\DiscordLoggerServiceProvider`
- If extending `DiscordNotificationService`, migrate to implementing `DiscordTransport` interface
- Queue job constructor signature changed

## [1.0.0] - 2026-01-06

### Added
- Initial release
- Real-time Discord notifications via webhooks
- Daily log report summaries
- Configurable log levels for notifications
- Rate limiting support
- Async queue support
- Laravel 10, 11, and 12 compatibility
