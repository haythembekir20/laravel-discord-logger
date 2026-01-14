<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Application\DailyReport;

final class DailyReportDTO
{
    public function __construct(
        public readonly LogStatisticsDTO $statistics,
        public readonly string $summary,
    ) {}
}
