<?php

namespace HaythemBekir\DiscordLogger\Logging;

use HaythemBekir\DiscordLogger\Services\DiscordNotificationService;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class DiscordLogHandler extends AbstractProcessingHandler
{
    protected DiscordNotificationService $discordService;

    public function __construct(int|string|Level $level = Level::Error, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->discordService = app(DiscordNotificationService::class);
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->discordService->isEnabled()) {
            return;
        }

        $this->discordService->sendLogNotification(
            $record->level->name,
            $record->message,
            $record->context
        );
    }
}
