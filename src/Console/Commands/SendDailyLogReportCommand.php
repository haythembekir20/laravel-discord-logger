<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Console\Commands;

use Carbon\Carbon;
use HaythemBekir\DiscordLogger\Application\DailyReport\BuildDailyReportAction;
use HaythemBekir\DiscordLogger\Application\DailyReport\DailyReportDTO;
use HaythemBekir\DiscordLogger\Application\DailyReport\GatherLogStatisticsAction;
use HaythemBekir\DiscordLogger\Application\DailyReport\SendDailyReportAction;
use HaythemBekir\DiscordLogger\Domain\Config\DiscordLoggerConfig;
use Illuminate\Console\Command;

final class SendDailyLogReportCommand extends Command
{
    protected $signature = 'discord-logger:daily-report
        {--date= : Date to report for (Y-m-d format). Defaults to yesterday.}
        {--dry-run : Preview the report without sending to Discord}';

    protected $description = 'Send daily log statistics report to Discord';

    public function handle(
        DiscordLoggerConfig $config,
        GatherLogStatisticsAction $gather,
        BuildDailyReportAction $build,
        SendDailyReportAction $send,
    ): int {
        $date = $this->parseDate();

        $this->info("Gathering log statistics for {$date->format('Y-m-d')}...");

        $statistics = $gather->execute($date);
        $report = $build->execute($statistics);

        if ($this->option('dry-run')) {
            $this->displayReport($report);

            return self::SUCCESS;
        }

        if (! $config->dailyReport->isConfigured()) {
            $this->error('Daily report is not configured. Please set DISCORD_LOGGER_DAILY_REPORT_ENABLED and DISCORD_LOGGER_DAILY_REPORT_WEBHOOK_URL.');

            return self::FAILURE;
        }

        $send->execute($report);

        $this->info("Daily report sent successfully for {$date->format('Y-m-d')}");

        return self::SUCCESS;
    }

    private function parseDate(): Carbon
    {
        $dateOption = $this->option('date');

        if ($dateOption !== null) {
            return Carbon::parse($dateOption);
        }

        return Carbon::yesterday();
    }

    private function displayReport(DailyReportDTO $report): void
    {
        $this->newLine();
        $this->info('=== Daily Log Report Preview ===');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Date', $report->statistics->date->format('Y-m-d')],
                ['Total Logs', $report->statistics->totalLogs],
                ['Summary', $report->summary],
            ]
        );

        $this->newLine();
        $this->info('=== Logs by Level ===');

        $levelData = [];
        foreach ($report->statistics->byLevel as $level => $count) {
            if ($count > 0) {
                $levelData[] = [ucfirst($level), $count];
            }
        }

        if (! empty($levelData)) {
            $this->table(['Level', 'Count'], $levelData);
        } else {
            $this->line('No logs recorded.');
        }

        if (! empty($report->statistics->byChannel)) {
            $this->newLine();
            $this->info('=== Logs by Channel ===');

            $channelData = [];
            foreach ($report->statistics->byChannel as $channel => $count) {
                $channelData[] = [$channel, $count];
            }

            $this->table(['Channel', 'Count'], $channelData);
        }

        if (! empty($report->statistics->topErrors)) {
            $this->newLine();
            $this->info('=== Top Recurring Errors ===');

            $errorData = [];
            foreach ($report->statistics->topErrors as $error) {
                $errorData[] = [
                    $error['count'],
                    substr($error['message'], 0, 80) . (strlen($error['message']) > 80 ? '...' : ''),
                ];
            }

            $this->table(['Count', 'Message'], $errorData);
        }

        $this->newLine();
        $this->warn('Dry run mode - report was not sent to Discord.');
    }
}
