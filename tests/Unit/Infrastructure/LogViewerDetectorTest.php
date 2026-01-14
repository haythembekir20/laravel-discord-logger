<?php

use HaythemBekir\DiscordLogger\Infrastructure\LogViewer\LogViewerDetector;

describe('LogViewerDetector', function () {
    it('can detect if no log viewer is installed', function () {
        $detector = new LogViewerDetector();

        expect($detector->isInstalled())->toBeFalse()
            ->and($detector->getInstalledPackage())->toBeNull();
    });

    it('returns null when generating URL without log viewer', function () {
        $detector = new LogViewerDetector();
        $date = new DateTime('2024-01-15');
        $error = 'foreach() argument must be of type array|object, null given';

        $url = $detector->generateLogViewerUrl('https://example.com', $date, $error);

        expect($url)->toBeNull();
    });

    it('returns null when app URL is not provided', function () {
        $detector = new LogViewerDetector();
        $date = new DateTime('2024-01-15');
        $error = 'foreach() argument must be of type array|object, null given';

        $url = $detector->generateLogViewerUrl(null, $date, $error);

        expect($url)->toBeNull();
    });
});
