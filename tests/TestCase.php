<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Tests;

use HaythemBekir\DiscordLogger\Providers\DiscordLoggerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DiscordLoggerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('discord-logger', [
            'daily_report' => [
                'enabled' => true,
                'webhook_url' => 'https://discord.com/api/webhooks/daily/token',
                'time' => '08:00',
                'timezone' => 'UTC',
            ],
            'appearance' => [
                'username' => 'Test Logger',
                'avatar_url' => null,
            ],
            'colors' => [
                'emergency' => 0xFF0000,
                'alert' => 0xFF3300,
                'critical' => 0xFF6600,
                'error' => 0xFF9900,
                'warning' => 0xFFCC00,
                'notice' => 0x00CCFF,
                'info' => 0x0099FF,
                'debug' => 0x999999,
            ],
            'environment_label' => 'testing',
        ]);
    }
}
