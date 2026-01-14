<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Application\DailyReport;

use DateTimeInterface;
use Illuminate\Support\Facades\File;
use SplFileObject;

final class GatherLogStatisticsAction
{
    private const LEVEL_PATTERNS = [
        'emergency' => '/\[[\d\-:\s]+\]\s+\w+\.EMERGENCY:/i',
        'alert'     => '/\[[\d\-:\s]+\]\s+\w+\.ALERT:/i',
        'critical'  => '/\[[\d\-:\s]+\]\s+\w+\.CRITICAL:/i',
        'error'     => '/\[[\d\-:\s]+\]\s+\w+\.ERROR:/i',
        'warning'   => '/\[[\d\-:\s]+\]\s+\w+\.WARNING:/i',
        'notice'    => '/\[[\d\-:\s]+\]\s+\w+\.NOTICE:/i',
        'info'      => '/\[[\d\-:\s]+\]\s+\w+\.INFO:/i',
        'debug'     => '/\[[\d\-:\s]+\]\s+\w+\.DEBUG:/i',
    ];

    private const MAX_ERROR_MESSAGE_LENGTH = 200;
    private const MAX_TOP_ERRORS = 10;

    public function execute(DateTimeInterface $date): LogStatisticsDTO
    {
        $logPath = storage_path('logs');
        $dateString = $date->format('Y-m-d');

        $stats = $this->initializeStats();
        $errorMessages = [];

        foreach ($this->findLogFiles($logPath, $dateString) as $file) {
            $channel = $this->extractChannelName($file);
            $this->analyzeFile($file, $stats, $errorMessages, $channel);
        }

        return new LogStatisticsDTO(
            date: $date,
            totalLogs: array_sum($stats['by_level']),
            byLevel: $stats['by_level'],
            byChannel: $stats['by_channel'],
            topErrors: $this->getTopErrors($errorMessages),
        );
    }

    private function initializeStats(): array
    {
        return [
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
        ];
    }

    /**
     * @return iterable<string>
     */
    private function findLogFiles(string $logPath, string $dateString): iterable
    {
        if (! File::isDirectory($logPath)) {
            return;
        }

        $files = File::files($logPath);

        foreach ($files as $file) {
            $filename = $file->getFilename();

            // Match files like laravel-2024-01-15.log or laravel.log
            if (str_contains($filename, $dateString) || $filename === 'laravel.log') {
                yield $file->getPathname();
            }
        }
    }

    private function extractChannelName(string $filePath): string
    {
        $filename = basename($filePath, '.log');

        // Remove date suffix if present (e.g., laravel-2024-01-15 -> laravel)
        return preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $filename) ?? $filename;
    }

    private function analyzeFile(string $filePath, array &$stats, array &$errorMessages, string $channel): void
    {
        try {
            $file = new SplFileObject($filePath, 'r');
            $file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

            foreach ($file as $line) {
                if (! is_string($line) || $line === '') {
                    continue;
                }

                $this->analyzeLine($line, $stats, $errorMessages, $channel);
            }
        } catch (\Exception) {
            // Skip files that cannot be read
        }
    }

    private function analyzeLine(string $line, array &$stats, array &$errorMessages, string $channel): void
    {
        foreach (self::LEVEL_PATTERNS as $level => $pattern) {
            if (preg_match($pattern, $line)) {
                $stats['by_level'][$level]++;
                $stats['by_channel'][$channel] = ($stats['by_channel'][$channel] ?? 0) + 1;

                // Track error messages for deduplication
                if (in_array($level, ['error', 'critical', 'alert', 'emergency'], true)) {
                    $message = $this->extractErrorMessage($line, $level);
                    if ($message !== null) {
                        $hash = md5($message);
                        $errorMessages[$hash] = [
                            'message' => $message,
                            'count' => ($errorMessages[$hash]['count'] ?? 0) + 1,
                        ];
                    }
                }

                break; // Only match one level per line
            }
        }
    }

    private function extractErrorMessage(string $line, string $level): ?string
    {
        $pattern = '/\.' . strtoupper($level) . ':\s*(.+?)(?:\s*\{|\s*\[|$)/i';

        if (preg_match($pattern, $line, $matches)) {
            $message = trim($matches[1]);
            $message = preg_replace('/\s+/', ' ', $message) ?? $message;

            if (strlen($message) > self::MAX_ERROR_MESSAGE_LENGTH) {
                $message = substr($message, 0, self::MAX_ERROR_MESSAGE_LENGTH) . '...';
            }

            return $message;
        }

        return null;
    }

    /**
     * @param  array<string, array{message: string, count: int}>  $errorMessages
     * @return array<array{message: string, count: int}>
     */
    private function getTopErrors(array $errorMessages): array
    {
        // Sort by count descending
        uasort($errorMessages, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_values(array_slice($errorMessages, 0, self::MAX_TOP_ERRORS));
    }
}
