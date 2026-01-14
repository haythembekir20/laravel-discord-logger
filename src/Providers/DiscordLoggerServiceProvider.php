<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Providers;

use HaythemBekir\DiscordLogger\Application\DailyReport\BuildDailyReportAction;
use HaythemBekir\DiscordLogger\Application\DailyReport\GatherLogStatisticsAction;
use HaythemBekir\DiscordLogger\Application\DailyReport\SendDailyReportAction;
use HaythemBekir\DiscordLogger\Console\Commands\SendDailyLogReportCommand;
use HaythemBekir\DiscordLogger\Domain\Config\DiscordLoggerConfig;
use HaythemBekir\DiscordLogger\Infrastructure\Http\DiscordWebhookClient;
use HaythemBekir\DiscordLogger\Infrastructure\LogViewer\LogViewerDetector;
use Illuminate\Contracts\Foundation\Application;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class DiscordLoggerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('discord-logger')
            ->hasConfigFile()
            ->hasCommand(SendDailyLogReportCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->registerConfig();
        $this->registerLogViewerDetector();
        $this->registerWebhookClient();
        $this->registerActions();
    }

    private function registerConfig(): void
    {
        $this->app->singleton(DiscordLoggerConfig::class, function (): DiscordLoggerConfig {
            return DiscordLoggerConfig::fromArray(
                config('discord-logger', [])
            );
        });
    }

    private function registerLogViewerDetector(): void
    {
        $this->app->singleton(LogViewerDetector::class);
    }

    private function registerWebhookClient(): void
    {
        $this->app->singleton(DiscordWebhookClient::class, function (Application $app): DiscordWebhookClient {
            $config = $app->make(DiscordLoggerConfig::class);

            return new DiscordWebhookClient($config->appearance);
        });
    }

    private function registerActions(): void
    {
        $this->app->singleton(GatherLogStatisticsAction::class);
        $this->app->singleton(BuildDailyReportAction::class);
        $this->app->singleton(SendDailyReportAction::class);
    }
}
