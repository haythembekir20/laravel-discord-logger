<?php

declare(strict_types=1);

use HaythemBekir\DiscordLogger\Application\DailyReport\BuildDailyReportAction;
use HaythemBekir\DiscordLogger\Application\DailyReport\GatherLogStatisticsAction;
use HaythemBekir\DiscordLogger\Application\DailyReport\SendDailyReportAction;
use HaythemBekir\DiscordLogger\Domain\Config\DiscordLoggerConfig;
use HaythemBekir\DiscordLogger\Infrastructure\Http\DiscordWebhookClient;

describe('DiscordLoggerServiceProvider', function () {
    it('registers DiscordLoggerConfig as singleton', function () {
        $config1 = app(DiscordLoggerConfig::class);
        $config2 = app(DiscordLoggerConfig::class);

        expect($config1)->toBeInstanceOf(DiscordLoggerConfig::class);
        expect($config1)->toBe($config2);
    });

    it('registers DiscordWebhookClient as singleton', function () {
        $client1 = app(DiscordWebhookClient::class);
        $client2 = app(DiscordWebhookClient::class);

        expect($client1)->toBeInstanceOf(DiscordWebhookClient::class);
        expect($client1)->toBe($client2);
    });

    it('registers all actions as singletons', function () {
        $actions = [
            GatherLogStatisticsAction::class,
            BuildDailyReportAction::class,
            SendDailyReportAction::class,
        ];

        foreach ($actions as $actionClass) {
            $action1 = app($actionClass);
            $action2 = app($actionClass);

            expect($action1)->toBeInstanceOf($actionClass);
            expect($action1)->toBe($action2);
        }
    });

    it('loads config from package', function () {
        expect(config('discord-logger'))->toBeArray();
        expect(config('discord-logger.daily_report'))->toBeArray();
    });
});
