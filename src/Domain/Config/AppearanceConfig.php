<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Domain\Config;

final class AppearanceConfig
{
    private const DEFAULT_COLORS = [
        'emergency' => 0xFF0000,
        'alert'     => 0xFF3300,
        'critical'  => 0xFF6600,
        'error'     => 0xFF9900,
        'warning'   => 0xFFCC00,
        'notice'    => 0x00CCFF,
        'info'      => 0x0099FF,
        'debug'     => 0x999999,
    ];

    public function __construct(
        public readonly string $username,
        public readonly ?string $avatarUrl,
        public readonly array $colors,
    ) {}

    public static function fromArray(array $config): self
    {
        $appearance = $config['appearance'] ?? [];

        return new self(
            username: $appearance['username'] ?? 'Laravel Logger',
            avatarUrl: $appearance['avatar_url'] ?? null,
            colors: $config['colors'] ?? self::DEFAULT_COLORS,
        );
    }

    public function getColorForLevel(string $level): int
    {
        return $this->colors[strtolower($level)] ?? 0x999999;
    }
}
