<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Application\DailyReport;

use HaythemBekir\DiscordLogger\Domain\Config\DiscordLoggerConfig;
use HaythemBekir\DiscordLogger\Domain\ValueObjects\DiscordMessage;
use HaythemBekir\DiscordLogger\Infrastructure\Http\DiscordWebhookClient;

final class SendDailyReportAction
{
    public function __construct(
        private readonly DiscordLoggerConfig $config,
        private readonly DiscordWebhookClient $webhookClient,
    ) {}

    public function execute(DailyReportDTO $report): bool
    {
        if (! $this->config->dailyReport->isConfigured()) {
            return false;
        }

        $message = DiscordMessage::forDailyReport(
            webhookUrl: $this->config->dailyReport->webhookUrl,
            date: $report->statistics->date,
            totalLogs: $report->statistics->totalLogs,
            byLevel: $report->statistics->byLevel,
            byChannel: $report->statistics->byChannel,
            topErrors: $report->statistics->topErrors,
            appearance: $this->config->appearance,
        );

        $this->webhookClient->send($message);

        return true;
    }
}
