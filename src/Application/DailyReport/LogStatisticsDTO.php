<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Application\DailyReport;

use DateTimeInterface;

final class LogStatisticsDTO
{
    /**
     * @param  array<string, int>  $byLevel
     * @param  array<string, int>  $byChannel
     * @param  array<array{message: string, count: int}>  $topErrors
     */
    public function __construct(
        public readonly DateTimeInterface $date,
        public readonly int $totalLogs,
        public readonly array $byLevel,
        public readonly array $byChannel,
        public readonly array $topErrors,
    ) {}

    public function hasErrors(): bool
    {
        return ($this->byLevel['error'] ?? 0) > 0
            || ($this->byLevel['critical'] ?? 0) > 0
            || ($this->byLevel['alert'] ?? 0) > 0
            || ($this->byLevel['emergency'] ?? 0) > 0;
    }

    public function isEmpty(): bool
    {
        return $this->totalLogs === 0;
    }
}
