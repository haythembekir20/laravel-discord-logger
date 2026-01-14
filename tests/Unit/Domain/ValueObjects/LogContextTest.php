<?php

declare(strict_types=1);

use HaythemBekir\DiscordLogger\Domain\ValueObjects\LogContext;

describe('LogContext', function () {
    it('creates from array', function () {
        $context = LogContext::fromArray(['user_id' => 123, 'action' => 'login']);

        expect($context->isEmpty())->toBeFalse();
    });

    it('filters out exception key', function () {
        $context = LogContext::fromArray([
            'user_id' => 123,
            'exception' => new Exception('Test'),
        ]);

        $json = $context->toJson();

        expect($json)->toContain('user_id');
        expect($json)->not->toContain('exception');
    });

    it('filters out trace key', function () {
        $context = LogContext::fromArray([
            'user_id' => 123,
            'trace' => ['frame1', 'frame2'],
        ]);

        $json = $context->toJson();

        expect($json)->toContain('user_id');
        expect($json)->not->toContain('trace');
    });

    it('filters out previous key', function () {
        $context = LogContext::fromArray([
            'user_id' => 123,
            'previous' => 'some value',
        ]);

        $json = $context->toJson();

        expect($json)->toContain('user_id');
        expect($json)->not->toContain('previous');
    });

    it('filters out objects', function () {
        $context = LogContext::fromArray([
            'user_id' => 123,
            'object' => new stdClass(),
        ]);

        $json = $context->toJson();

        expect($json)->toContain('user_id');
        expect($json)->not->toContain('object');
    });

    it('returns empty object JSON for empty context', function () {
        $context = LogContext::fromArray([]);

        expect($context->toJson())->toBe('{}');
        expect($context->isEmpty())->toBeTrue();
    });

    it('truncates long JSON output', function () {
        $longData = [];
        for ($i = 0; $i < 100; $i++) {
            $longData["key_{$i}"] = str_repeat('a', 50);
        }

        $context = LogContext::fromArray($longData);
        $json = $context->toJson(100);

        expect(strlen($json))->toBeLessThanOrEqual(103); // 100 + "..."
    });

    it('returns pretty printed JSON', function () {
        $context = LogContext::fromArray(['user_id' => 123, 'action' => 'login']);

        $json = $context->toJson();

        expect($json)->toContain("\n");
    });
});
