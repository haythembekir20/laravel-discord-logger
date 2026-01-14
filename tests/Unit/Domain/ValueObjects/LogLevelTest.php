<?php

declare(strict_types=1);

use HaythemBekir\DiscordLogger\Domain\ValueObjects\LogLevel;
use Monolog\Level;

describe('LogLevel', function () {
    it('returns correct emoji for each level', function (LogLevel $level, string $expectedEmoji) {
        expect($level->emoji())->toBe($expectedEmoji);
    })->with([
        'emergency' => [LogLevel::Emergency, '🚨'],
        'alert' => [LogLevel::Alert, '🔔'],
        'critical' => [LogLevel::Critical, '💥'],
        'error' => [LogLevel::Error, '❌'],
        'warning' => [LogLevel::Warning, '⚠️'],
        'notice' => [LogLevel::Notice, '📝'],
        'info' => [LogLevel::Info, 'ℹ️'],
        'debug' => [LogLevel::Debug, '🔍'],
    ]);

    it('converts from Monolog level', function (Level $monologLevel, LogLevel $expectedLevel) {
        expect(LogLevel::fromMonolog($monologLevel))->toBe($expectedLevel);
    })->with([
        'emergency' => [Level::Emergency, LogLevel::Emergency],
        'alert' => [Level::Alert, LogLevel::Alert],
        'critical' => [Level::Critical, LogLevel::Critical],
        'error' => [Level::Error, LogLevel::Error],
        'warning' => [Level::Warning, LogLevel::Warning],
        'notice' => [Level::Notice, LogLevel::Notice],
        'info' => [Level::Info, LogLevel::Info],
        'debug' => [Level::Debug, LogLevel::Debug],
    ]);

    it('converts from string', function (string $levelString, LogLevel $expectedLevel) {
        expect(LogLevel::fromString($levelString))->toBe($expectedLevel);
    })->with([
        'lowercase' => ['error', LogLevel::Error],
        'uppercase' => ['ERROR', LogLevel::Error],
        'mixed case' => ['Error', LogLevel::Error],
    ]);

    it('returns correct color from color map', function () {
        $colorMap = [
            'emergency' => 0xFF0000,
            'error' => 0xFF9900,
            'warning' => 0xFFCC00,
        ];

        expect(LogLevel::Emergency->color($colorMap))->toBe(0xFF0000);
        expect(LogLevel::Error->color($colorMap))->toBe(0xFF9900);
        expect(LogLevel::Warning->color($colorMap))->toBe(0xFFCC00);
    });

    it('returns default color when level not in map', function () {
        $colorMap = ['error' => 0xFF9900];

        expect(LogLevel::Debug->color($colorMap))->toBe(0x999999);
    });
});
