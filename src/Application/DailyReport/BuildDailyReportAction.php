<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Application\DailyReport;

final class BuildDailyReportAction
{
    public function execute(LogStatisticsDTO $statistics): DailyReportDTO
    {
        $summary = $this->buildSummary($statistics);

        return new DailyReportDTO(
            statistics: $statistics,
            summary: $summary,
        );
    }

    private function buildSummary(LogStatisticsDTO $statistics): string
    {
        if ($statistics->isEmpty()) {
            return 'No logs recorded for this period.';
        }

        if ($statistics->hasErrors()) {
            $errorCount = ($statistics->byLevel['error'] ?? 0)
                + ($statistics->byLevel['critical'] ?? 0)
                + ($statistics->byLevel['alert'] ?? 0)
                + ($statistics->byLevel['emergency'] ?? 0);

            return "Issues detected: {$errorCount} error-level log entries found.";
        }

        return 'All systems operating normally. No errors detected.';
    }
}
