<?php

namespace HaythemBekir\DiscordLogger\Console\Commands;

use HaythemBekir\DiscordLogger\Services\DiscordNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SendDailyLogReport extends Command
{
    protected $signature = 'discord-logger:daily-report
                            {--date= : The date to generate report for (Y-m-d format, defaults to yesterday)}
                            {--dry-run : Show statistics without sending to Discord}';

    protected $description = 'Generate and send daily log statistics report to Discord';

    protected array $levelPatterns = [
        'emergency' => '/\[[\d\-:\s]+\]\s+\w+\.EMERGENCY:/i',
        'alert' => '/\[[\d\-:\s]+\]\s+\w+\.ALERT:/i',
        'critical' => '/\[[\d\-:\s]+\]\s+\w+\.CRITICAL:/i',
        'error' => '/\[[\d\-:\s]+\]\s+\w+\.ERROR:/i',
        'warning' => '/\[[\d\-:\s]+\]\s+\w+\.WARNING:/i',
        'notice' => '/\[[\d\-:\s]+\]\s+\w+\.NOTICE:/i',
        'info' => '/\[[\d\-:\s]+\]\s+\w+\.INFO:/i',
        'debug' => '/\[[\d\-:\s]+\]\s+\w+\.DEBUG:/i',
    ];

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $this->info("Generating log report for: {$date->format('Y-m-d')}");

        $statistics = $this->gatherStatistics($date);

        if ($this->option('dry-run')) {
            $this->displayStatistics($statistics);
            return Command::SUCCESS;
        }

        $discordService = app(DiscordNotificationService::class);

        if (!config('discord-logger.daily_report.enabled', true)) {
            $this->warn('Daily report is disabled in configuration.');
            return Command::SUCCESS;
        }

        $success = $discordService->sendDailyReport($statistics);

        if ($success) {
            $this->info('Daily log report sent to Discord successfully.');
        } else {
            $this->error('Failed to send daily log report to Discord.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function gatherStatistics(Carbon $date): array
    {
        $logPath = storage_path('logs');
        $dateString = $date->format('Y-m-d');

        $statistics = [
            'date' => $dateString,
            'total' => 0,
            'by_level' => [
                'emergency' => 0,
                'alert' => 0,
                'critical' => 0,
                'error' => 0,
                'warning' => 0,
                'notice' => 0,
                'info' => 0,
                'debug' => 0,
            ],
            'by_channel' => [],
            'top_errors' => [],
        ];

        $errorMessages = [];

        $logFiles = $this->findLogFilesForDate($logPath, $date);

        foreach ($logFiles as $logFile) {
            $channelName = $this->extractChannelName($logFile);
            $fileStats = $this->analyzeLogFile($logFile, $dateString, $errorMessages);

            if ($fileStats['total'] > 0) {
                $statistics['by_channel'][$channelName] = ($statistics['by_channel'][$channelName] ?? 0) + $fileStats['total'];
                $statistics['total'] += $fileStats['total'];

                foreach ($fileStats['by_level'] as $level => $count) {
                    $statistics['by_level'][$level] += $count;
                }
            }
        }

        arsort($statistics['by_channel']);

        $statistics['top_errors'] = $this->getTopErrors($errorMessages);

        return $statistics;
    }

    protected function findLogFilesForDate(string $logPath, Carbon $date): array
    {
        $files = [];
        $dateString = $date->format('Y-m-d');

        if (!File::isDirectory($logPath)) {
            return $files;
        }

        $allFiles = File::allFiles($logPath);

        foreach ($allFiles as $file) {
            $filename = $file->getFilename();

            if (!Str::endsWith($filename, '.log')) {
                continue;
            }

            if (Str::contains($filename, $dateString)) {
                $files[] = $file->getPathname();
                continue;
            }

            if ($filename === 'laravel.log') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    protected function extractChannelName(string $filePath): string
    {
        $filename = basename($filePath);

        $filename = preg_replace('/-\d{4}-\d{2}-\d{2}\.log$/', '', $filename);
        $filename = preg_replace('/\.log$/', '', $filename);

        return $filename ?: 'default';
    }

    protected function analyzeLogFile(string $filePath, string $dateString, array &$errorMessages): array
    {
        $stats = [
            'total' => 0,
            'by_level' => [
                'emergency' => 0,
                'alert' => 0,
                'critical' => 0,
                'error' => 0,
                'warning' => 0,
                'notice' => 0,
                'info' => 0,
                'debug' => 0,
            ],
        ];

        if (!File::exists($filePath)) {
            return $stats;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return $stats;
        }

        $currentEntry = '';
        $inTargetDate = false;

        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                if ($currentEntry && $inTargetDate) {
                    $this->processLogEntry($currentEntry, $stats, $errorMessages);
                }

                $inTargetDate = ($matches[1] === $dateString);
                $currentEntry = $line;
            } else {
                $currentEntry .= $line;
            }
        }

        if ($currentEntry && $inTargetDate) {
            $this->processLogEntry($currentEntry, $stats, $errorMessages);
        }

        fclose($handle);

        return $stats;
    }

    protected function processLogEntry(string $entry, array &$stats, array &$errorMessages): void
    {
        foreach ($this->levelPatterns as $level => $pattern) {
            if (preg_match($pattern, $entry)) {
                $stats['by_level'][$level]++;
                $stats['total']++;

                if (in_array($level, ['emergency', 'alert', 'critical', 'error'])) {
                    $message = $this->extractErrorMessage($entry);
                    if ($message) {
                        $hash = md5($message);
                        if (!isset($errorMessages[$hash])) {
                            $errorMessages[$hash] = [
                                'message' => $message,
                                'count' => 0,
                            ];
                        }
                        $errorMessages[$hash]['count']++;
                    }
                }

                return;
            }
        }
    }

    protected function extractErrorMessage(string $entry): ?string
    {
        if (preg_match('/\.\w+:\s*(.+?)(?:\s*\{|\s*$)/s', $entry, $matches)) {
            $message = trim($matches[1]);
            $message = preg_replace('/\s+/', ' ', $message);
            return Str::limit($message, 200);
        }

        return null;
    }

    protected function getTopErrors(array $errorMessages): array
    {
        usort($errorMessages, fn($a, $b) => $b['count'] - $a['count']);

        return array_slice($errorMessages, 0, 10);
    }

    protected function displayStatistics(array $statistics): void
    {
        $this->newLine();
        $this->info("===== Log Statistics for {$statistics['date']} =====");
        $this->newLine();

        $this->info("Total Logs: " . number_format($statistics['total']));
        $this->newLine();

        $this->info("By Level:");
        $this->table(
            ['Level', 'Count'],
            collect($statistics['by_level'])
                ->filter(fn($count) => $count > 0)
                ->map(fn($count, $level) => [ucfirst($level), number_format($count)])
                ->values()
                ->toArray()
        );

        if (!empty($statistics['by_channel'])) {
            $this->newLine();
            $this->info("By Channel:");
            $this->table(
                ['Channel', 'Count'],
                collect($statistics['by_channel'])
                    ->map(fn($count, $channel) => [$channel, number_format($count)])
                    ->values()
                    ->toArray()
            );
        }

        if (!empty($statistics['top_errors'])) {
            $this->newLine();
            $this->info("Top Recurring Errors:");
            $this->table(
                ['Count', 'Message'],
                collect($statistics['top_errors'])
                    ->map(fn($error) => [
                        $error['count'],
                        Str::limit($error['message'], 80),
                    ])
                    ->toArray()
            );
        }
    }
}
