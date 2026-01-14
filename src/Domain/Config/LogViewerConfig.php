<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Domain\Config;

final class LogViewerConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly ?string $appUrl,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            enabled: $config['enabled'] ?? true,
            appUrl: $config['app_url'] ?? null,
        );
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->appUrl !== null;
    }
}
