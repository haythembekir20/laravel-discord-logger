<?php

namespace HaythemBekir\DiscordLogger\Logging;

use Monolog\Logger;

class CreateDiscordLogger
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('discord');

        $level = $config['level'] ?? 'error';
        $handler = new DiscordLogHandler($level);

        $logger->pushHandler($handler);

        return $logger;
    }
}
