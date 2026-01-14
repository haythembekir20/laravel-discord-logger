<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Domain\ValueObjects;

use Monolog\Level;

enum LogLevel: string
{
    case Emergency = 'emergency';
    case Alert = 'alert';
    case Critical = 'critical';
    case Error = 'error';
    case Warning = 'warning';
    case Notice = 'notice';
    case Info = 'info';
    case Debug = 'debug';

    public function emoji(): string
    {
        return match ($this) {
            self::Emergency => '🚨',
            self::Alert => '🔔',
            self::Critical => '💥',
            self::Error => '❌',
            self::Warning => '⚠️',
            self::Notice => '📝',
            self::Info => 'ℹ️',
            self::Debug => '🔍',
        };
    }

    public function isSevere(): bool
    {
        return in_array($this, [self::Emergency, self::Alert, self::Critical, self::Error], true);
    }

    public static function fromMonolog(Level $level): self
    {
        return self::from(strtolower($level->name));
    }

    public static function fromString(string $level): self
    {
        return self::from(strtolower($level));
    }

    public static function tryFromString(string $level): ?self
    {
        return self::tryFrom(strtolower($level));
    }

    public function color(array $colorMap): int
    {
        return $colorMap[$this->value] ?? 0x999999;
    }
}
