<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Domain\Config;

final class DailyReportConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly ?string $webhookUrl,
        public readonly string $time,
        public readonly string $timezone,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            enabled: (bool) ($config['enabled'] ?? false),
            webhookUrl: $config['webhook_url'] ?? null,
            time: $config['time'] ?? '08:00',
            timezone: $config['timezone'] ?? 'UTC',
        );
    }

    public function isConfigured(): bool
    {
        return $this->enabled && $this->webhookUrl !== null && $this->webhookUrl !== '';
    }
}
