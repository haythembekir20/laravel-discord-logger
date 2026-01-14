<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Infrastructure\Http;

use HaythemBekir\DiscordLogger\Domain\Config\AppearanceConfig;
use HaythemBekir\DiscordLogger\Domain\ValueObjects\DiscordMessage;
use Illuminate\Support\Facades\Http;

final class DiscordWebhookClient
{
    public function __construct(
        private readonly AppearanceConfig $appearance,
    ) {}

    public function send(DiscordMessage $message): void
    {
        Http::timeout(10)->post($message->webhookUrl, [
            'username' => $this->appearance->username,
            'avatar_url' => $this->appearance->avatarUrl,
            'embeds' => [$message->toEmbed()],
        ]);
    }
}
