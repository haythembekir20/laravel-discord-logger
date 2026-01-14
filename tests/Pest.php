<?php

declare(strict_types=1);

use HaythemBekir\DiscordLogger\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeValidWebhookUrl', function () {
    return $this->toBeString()
        ->toStartWith('https://discord.com/api/webhooks/');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createTestConfig(array $overrides = []): array
{
    return array_replace_recursive([
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
    ], $overrides);
}
