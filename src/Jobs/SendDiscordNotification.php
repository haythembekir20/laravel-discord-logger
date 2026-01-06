<?php

namespace HaythemBekir\DiscordLogger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendDiscordNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;
    public int $timeout = 15;

    public function __construct(
        protected string $level,
        protected string $message,
        protected array $context,
        protected array $config
    ) {}

    public function handle(): void
    {
        $payload = $this->buildPayload();

        Http::timeout(10)->post($this->config['webhook_url'], $payload);
    }

    protected function buildPayload(): array
    {
        $colors = $this->config['colors'] ?? [];
        $color = $colors[strtolower($this->level)] ?? 0xFF0000;
        $environment = $this->config['environment_label'] ?? 'production';

        $fields = [
            ['name' => 'Level', 'value' => strtoupper($this->level), 'inline' => true],
            ['name' => 'Environment', 'value' => $environment, 'inline' => true],
            ['name' => 'Time', 'value' => now()->format('Y-m-d H:i:s'), 'inline' => true],
        ];

        if (!empty($this->context)) {
            $contextString = json_encode(
                array_filter($this->context, fn($v, $k) => !in_array($k, ['exception', 'trace']) && !is_object($v), ARRAY_FILTER_USE_BOTH),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
            if (strlen($contextString) > 1024) {
                $contextString = substr($contextString, 0, 1021) . '...';
            }
            $fields[] = ['name' => 'Context', 'value' => "```json\n{$contextString}\n```", 'inline' => false];
        }

        $truncatedMessage = strlen($this->message) > 2048
            ? substr($this->message, 0, 2045) . '...'
            : $this->message;

        $payload = [
            'embeds' => [[
                'title' => "Log Alert: {$this->level}",
                'description' => "```\n{$truncatedMessage}\n```",
                'color' => $color,
                'fields' => $fields,
                'footer' => ['text' => config('app.name', 'Laravel') . ' Logger'],
                'timestamp' => now()->toIso8601String(),
            ]],
        ];

        if (!empty($this->config['appearance']['username'])) {
            $payload['username'] = $this->config['appearance']['username'];
        }

        return $payload;
    }
}
