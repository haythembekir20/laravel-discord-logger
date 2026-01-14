<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Domain\ValueObjects;

use Illuminate\Support\Str;

final class LogContext
{
    private const EXCLUDED_KEYS = ['exception', 'trace', 'previous'];

    public function __construct(
        private readonly array $data,
    ) {}

    public static function fromArray(array $context): self
    {
        $filtered = [];

        foreach ($context as $key => $value) {
            if (in_array($key, self::EXCLUDED_KEYS, true)) {
                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return new self($filtered);
    }

    public function toJson(int $maxLength = 1024): string
    {
        if ($this->isEmpty()) {
            return '{}';
        }

        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return '{}';
        }

        return Str::limit($json, $maxLength, '...');
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
