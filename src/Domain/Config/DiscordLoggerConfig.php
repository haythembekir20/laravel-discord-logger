<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Domain\Config;

final class DiscordLoggerConfig
{
    public function __construct(
        public readonly DailyReportConfig $dailyReport,
        public readonly AppearanceConfig $appearance,
        public readonly LogViewerConfig $logViewer,
        public readonly string $environmentLabel,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            dailyReport: DailyReportConfig::fromArray($config['daily_report'] ?? []),
            appearance: AppearanceConfig::fromArray($config),
            logViewer: LogViewerConfig::fromArray($config['log_viewer'] ?? []),
            environmentLabel: $config['environment_label'] ?? 'production',
        );
    }

    public function isEnabled(): bool
    {
        return $this->dailyReport->isConfigured();
    }
}
