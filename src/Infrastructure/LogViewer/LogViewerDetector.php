<?php

declare(strict_types=1);

namespace HaythemBekir\DiscordLogger\Infrastructure\LogViewer;

use DateTimeInterface;

final class LogViewerDetector
{
    private ?string $detectedPackage = null;

    public function __construct()
    {
        $this->detectInstalledPackage();
    }

    public function isInstalled(): bool
    {
        return $this->detectedPackage !== null;
    }

    public function getInstalledPackage(): ?string
    {
        return $this->detectedPackage;
    }

    /**
     * Generate a clickable URL to the log viewer for a specific date and error message.
     */
    public function generateLogViewerUrl(?string $appUrl, DateTimeInterface $date, string $errorMessage): ?string
    {
        if (! $this->isInstalled() || $appUrl === null) {
            return null;
        }

        $appUrl = rtrim($appUrl, '/');
        $dateString = $date->format('Y-m-d');

        return match ($this->detectedPackage) {
            'opcodesio/log-viewer' => $this->generateOpcodesUrl($appUrl, $dateString, $errorMessage),
            'rap2hpoutre/laravel-log-viewer' => $this->generateRap2hpoutreUrl($appUrl),
            default => null,
        };
    }

    private function detectInstalledPackage(): void
    {
        // Check for opcodesio/log-viewer (most popular, modern package)
        if (class_exists(\Opcodes\LogViewer\LogViewerServiceProvider::class)) {
            $this->detectedPackage = 'opcodesio/log-viewer';
            return;
        }

        // Check for rap2hpoutre/laravel-log-viewer (older, simpler package)
        if (class_exists(\Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class)) {
            $this->detectedPackage = 'rap2hpoutre/laravel-log-viewer';
            return;
        }

        $this->detectedPackage = null;
    }

    private function generateOpcodesUrl(string $appUrl, string $dateString, string $errorMessage): string
    {
        // opcodesio/log-viewer uses route: /log-viewer
        // We can filter by date using query parameters
        $searchQuery = $this->extractSearchableKeyword($errorMessage);

        return $searchQuery !== null
            ? "{$appUrl}/log-viewer?query=" . urlencode($searchQuery)
            : "{$appUrl}/log-viewer";
    }

    private function generateRap2hpoutreUrl(string $appUrl): string
    {
        // rap2hpoutre/laravel-log-viewer uses route: /logs
        // This package doesn't support deep linking to specific errors
        return "{$appUrl}/logs";
    }

    /**
     * Extract a searchable keyword from the error message.
     * This helps users quickly find the specific error in the log viewer.
     */
    private function extractSearchableKeyword(string $errorMessage): ?string
    {
        // Try to extract the most meaningful part of the error message
        // Remove common prefixes and get the core error

        // Handle PHP errors with specific patterns
        if (preg_match('/foreach\(\) argument must be of type/', $errorMessage)) {
            return 'foreach() argument must be of type';
        }

        if (preg_match('/syntax error, unexpected token/', $errorMessage)) {
            return 'syntax error, unexpected token';
        }

        if (preg_match('/Undefined (variable|array key|property)/', $errorMessage, $matches)) {
            return $matches[0];
        }

        if (preg_match('/Call to undefined (method|function)/', $errorMessage, $matches)) {
            return $matches[0];
        }

        // For other errors, try to get the first 50 characters as a reasonable search term
        $keyword = substr($errorMessage, 0, 50);

        return trim($keyword) !== '' ? $keyword : null;
    }
}
