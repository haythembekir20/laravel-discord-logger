<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Application\DailyReport;

use HaythemBekir\DiscordLogger\Domain\Config\DiscordLoggerConfig;
use HaythemBekir\DiscordLogger\Domain\ValueObjects\DiscordMessage;
use HaythemBekir\DiscordLogger\Infrastructure\Http\DiscordWebhookClient;
use HaythemBekir\DiscordLogger\Infrastructure\LogViewer\LogViewerDetector;

final class SendDailyReportAction
{
    public function __construct(
        private readonly DiscordLoggerConfig $config,
        private readonly DiscordWebhookClient $webhookClient,
        private readonly LogViewerDetector $logViewerDetector,
    ) {}

    public function execute(DailyReportDTO $report): bool
    {
        if (! $this->config->dailyReport->isConfigured()) {
            return false;
        }

        // Generate clickable links for errors if log viewer is available
        $errorLinks = $this->generateErrorLinks($report);

        $message = DiscordMessage::forDailyReport(
            webhookUrl: $this->config->dailyReport->webhookUrl,
            date: $report->statistics->date,
            totalLogs: $report->statistics->totalLogs,
            byLevel: $report->statistics->byLevel,
            byChannel: $report->statistics->byChannel,
            topErrors: $report->statistics->topErrors,
            appearance: $this->config->appearance,
            errorLinks: $errorLinks,
        );

        $this->webhookClient->send($message);

        return true;
    }

    /**
     * Generate clickable log viewer URLs for each error.
     *
     * @return array<int, string>|null
     */
    private function generateErrorLinks(DailyReportDTO $report): ?array
    {
        if (! $this->config->logViewer->isEnabled() || ! $this->logViewerDetector->isInstalled()) {
            return null;
        }

        $links = [];
        foreach ($report->statistics->topErrors as $error) {
            $url = $this->logViewerDetector->generateLogViewerUrl(
                $this->config->logViewer->appUrl,
                $report->statistics->date,
                $error['message']
            );

            if ($url !== null) {
                $links[] = $url;
            }
        }

        return ! empty($links) ? $links : null;
    }
}
