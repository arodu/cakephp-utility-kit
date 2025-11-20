<?php

declare(strict_types=1);

namespace UtilityKit\Utility;

final class ComposerManifest
{
    /**
     * Get the version from composer.lock for a given package.
     */
    public static function getPackageVersion(string $packageName): ?string
    {
        $composerLockData = self::getComposerLockData();

        if ($composerLockData === null || !isset($composerLockData['packages'])) {
            return null;
        }

        foreach ($composerLockData['packages'] as $package) {
            if (isset($package['name']) && $package['name'] === $packageName) {
                return $package['version'] ?? null;
            }
        }

        return null;
    }

    /**
     * Internal loader for composer.lock data with caching.
     */
    private static function getComposerLockData(): ?array
    {
        static $composerLockData = null;

        if ($composerLockData !== null) {
            return $composerLockData;
        }

        $composerLockPath = ROOT . DS . 'composer.lock';

        if (!file_exists($composerLockPath)) {
            return null;
        }

        $content = file_get_contents($composerLockPath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        $composerLockData = is_array($data) ? $data : null;

        return $composerLockData;
    }
}