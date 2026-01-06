<?php

namespace HaythemBekir\DiscordLogger;

use HaythemBekir\DiscordLogger\Console\Commands\SendDailyLogReport;
use HaythemBekir\DiscordLogger\Services\DiscordNotificationService;
use Illuminate\Support\ServiceProvider;

class DiscordLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/discord-logger.php',
            'discord-logger'
        );

        $this->app->singleton(DiscordNotificationService::class, function ($app) {
            return new DiscordNotificationService();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/discord-logger.php' => config_path('discord-logger.php'),
            ], 'discord-logger-config');

            $this->commands([
                SendDailyLogReport::class,
            ]);
        }

        $this->registerScheduledCommand();
    }

    protected function registerScheduledCommand(): void
    {
        $this->callAfterResolving(\Illuminate\Console\Scheduling\Schedule::class, function ($schedule) {
            if (config('discord-logger.daily_report.enabled', false)) {
                $time = config('discord-logger.daily_report.time', '08:00');
                $timezone = config('discord-logger.daily_report.timezone', 'UTC');

                $schedule->command('discord-logger:daily-report')
                    ->dailyAt($time)
                    ->timezone($timezone);
            }
        });
    }
}
