<?php

namespace HaythemBekir\DiscordLogger\Services;

use HaythemBekir\DiscordLogger\Jobs\SendDiscordNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordNotificationService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('discord-logger') ?? [];
    }

    public function isRealtimeEnabled(): bool
    {
        $realtime = $this->config['realtime'] ?? [];
        return ($realtime['enabled'] ?? false) && !empty($realtime['webhook_url']);
    }

    public function isDailyReportEnabled(): bool
    {
        $daily = $this->config['daily_report'] ?? [];
        return ($daily['enabled'] ?? false) && !empty($daily['webhook_url']);
    }

    /**
     * For backward compatibility
     */
    public function isEnabled(): bool
    {
        return $this->isRealtimeEnabled();
    }

    /**
     * Send real-time log notification (async by default for zero performance impact)
     */
    public function sendLogNotification(string $level, string $message, array $context = []): bool
    {
        if (!$this->isRealtimeEnabled()) {
            return false;
        }

        $realtime = $this->config['realtime'] ?? [];

        if (!$this->shouldNotifyLevel($level, $realtime['notify_levels'] ?? [])) {
            return false;
        }

        if (!$this->checkRateLimit($realtime['rate_limit'] ?? [])) {
            return false;
        }

        $jobConfig = [
            'webhook_url' => $realtime['webhook_url'],
            'async' => $realtime['async'] ?? true,
            'colors' => $this->config['colors'] ?? [],
            'appearance' => $this->config['appearance'] ?? [],
            'environment_label' => $this->config['environment_label'] ?? config('app.env'),
        ];

        // Use async mode by default (zero performance impact)
        if ($jobConfig['async']) {
            SendDiscordNotification::dispatch($level, $message, $context, $jobConfig);
            return true;
        }

        // Fallback to sync if async is disabled
        return $this->sendEmbed($this->buildLogEmbed($level, $message, $context), $realtime['webhook_url']);
    }

    /**
     * Send daily report to Discord
     */
    public function sendDailyReport(array $statistics): bool
    {
        if (!$this->isDailyReportEnabled()) {
            return false;
        }

        $webhookUrl = $this->config['daily_report']['webhook_url'];

        return $this->sendEmbed($this->buildReportEmbed($statistics), $webhookUrl);
    }

    protected function shouldNotifyLevel(string $level, array $notifyLevels): bool
    {
        if (empty($notifyLevels)) {
            $notifyLevels = ['emergency', 'alert', 'critical', 'error'];
        }
        return in_array(strtolower($level), array_map('strtolower', $notifyLevels));
    }

    protected function checkRateLimit(array $rateLimitConfig): bool
    {
        if (!($rateLimitConfig['enabled'] ?? true)) {
            return true;
        }

        $maxPerMinute = $rateLimitConfig['max_per_minute'] ?? 10;
        $cacheKey = 'discord_logger_rate_limit';

        $currentCount = Cache::get($cacheKey, 0);

        if ($currentCount >= $maxPerMinute) {
            return false;
        }

        Cache::put($cacheKey, $currentCount + 1, now()->addMinute());

        return true;
    }

    public function buildLogEmbed(string $level, string $message, array $context = []): array
    {
        $colors = $this->config['colors'] ?? [];
        $color = $colors[strtolower($level)] ?? 0xFF0000;
        $environment = $this->config['environment_label'] ?? config('app.env');

        $fields = [
            ['name' => 'Level', 'value' => strtoupper($level), 'inline' => true],
            ['name' => 'Environment', 'value' => $environment, 'inline' => true],
            ['name' => 'Time', 'value' => now()->format('Y-m-d H:i:s'), 'inline' => true],
        ];

        if (!empty($context)) {
            $contextString = $this->formatContext($context);
            if (strlen($contextString) > 1024) {
                $contextString = substr($contextString, 0, 1021) . '...';
            }
            $fields[] = [
                'name' => 'Context',
                'value' => "```json\n{$contextString}\n```",
                'inline' => false,
            ];
        }

        $truncatedMessage = strlen($message) > 2048
            ? substr($message, 0, 2045) . '...'
            : $message;

        return [
            'embeds' => [[
                'title' => "Log Alert: {$level}",
                'description' => "```\n{$truncatedMessage}\n```",
                'color' => $color,
                'fields' => $fields,
                'footer' => ['text' => config('app.name', 'Laravel') . ' Logger'],
                'timestamp' => now()->toIso8601String(),
            ]],
        ];
    }

    protected function buildReportEmbed(array $statistics): array
    {
        $environment = $this->config['environment_label'] ?? config('app.env');
        $reportDate = $statistics['date'] ?? now()->subDay()->format('Y-m-d');

        $fields = [];

        if (isset($statistics['total'])) {
            $fields[] = [
                'name' => 'Total Logs',
                'value' => number_format($statistics['total']),
                'inline' => true,
            ];
        }

        $levelOrder = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        foreach ($levelOrder as $level) {
            if (isset($statistics['by_level'][$level]) && $statistics['by_level'][$level] > 0) {
                $emoji = $this->getLevelEmoji($level);
                $fields[] = [
                    'name' => "{$emoji} " . ucfirst($level),
                    'value' => number_format($statistics['by_level'][$level]),
                    'inline' => true,
                ];
            }
        }

        if (isset($statistics['by_channel']) && !empty($statistics['by_channel'])) {
            $channelSummary = [];
            foreach ($statistics['by_channel'] as $channel => $count) {
                $channelSummary[] = "**{$channel}**: " . number_format($count);
            }
            $fields[] = [
                'name' => 'By Channel',
                'value' => implode("\n", array_slice($channelSummary, 0, 10)),
                'inline' => false,
            ];
        }

        if (isset($statistics['top_errors']) && !empty($statistics['top_errors'])) {
            $topErrors = [];
            foreach (array_slice($statistics['top_errors'], 0, 5) as $error) {
                $truncatedMsg = strlen($error['message']) > 50
                    ? substr($error['message'], 0, 47) . '...'
                    : $error['message'];
                $topErrors[] = "({$error['count']}x) {$truncatedMsg}";
            }
            $fields[] = [
                'name' => 'Top Recurring Errors',
                'value' => implode("\n", $topErrors),
                'inline' => false,
            ];
        }

        $hasErrors = ($statistics['by_level']['emergency'] ?? 0) > 0
            || ($statistics['by_level']['alert'] ?? 0) > 0
            || ($statistics['by_level']['critical'] ?? 0) > 0
            || ($statistics['by_level']['error'] ?? 0) > 0;

        $color = $hasErrors ? 0xFF6600 : 0x00CC00;

        return [
            'embeds' => [[
                'title' => "Daily Log Report - {$reportDate}",
                'description' => "Log statistics for **{$environment}** environment",
                'color' => $color,
                'fields' => $fields,
                'footer' => ['text' => config('app.name', 'Laravel') . ' Logger'],
                'timestamp' => now()->toIso8601String(),
            ]],
        ];
    }

    protected function getLevelEmoji(string $level): string
    {
        return match (strtolower($level)) {
            'emergency' => '🚨',
            'alert' => '🔔',
            'critical' => '💀',
            'error' => '❌',
            'warning' => '⚠️',
            'notice' => '📢',
            'info' => 'ℹ️',
            'debug' => '🔧',
            default => '📝',
        };
    }

    protected function formatContext(array $context): string
    {
        $filtered = array_filter($context, function ($value, $key) {
            return !in_array($key, ['exception', 'trace']) && !is_object($value);
        }, ARRAY_FILTER_USE_BOTH);

        return json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function sendEmbed(array $payload, string $webhookUrl): bool
    {
        if (empty($webhookUrl)) {
            return false;
        }

        $appearance = $this->config['appearance'] ?? [];

        if (!empty($appearance['username'])) {
            $payload['username'] = $appearance['username'];
        }

        if (!empty($appearance['avatar_url'])) {
            $payload['avatar_url'] = $appearance['avatar_url'];
        }

        try {
            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::channel('single')->warning('Discord webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('single')->error('Discord webhook exception', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
