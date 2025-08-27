<?php

declare(strict_types=1);

namespace UtilityKit\Utility;

/**
 * Common utility functions.
 */
final class Common
{
    /**
     * Get the composer.lock data.
     *
     * @return array|null The composer.lock data or null if the file is not found or invalid.
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

        $composerLockData = json_decode($content, true);

        return is_array($composerLockData) ? $composerLockData : null;
    }

    /**
     * Get the copyright year string.
     * 
     * @param int $startYear The starting year.
     * @return string
     */
    public static function getCopyrightYear(int $startYear): string
    {
        $currentYear = (int) date('Y');

        if ($startYear === $currentYear) {
            return (string) $currentYear;
        }

        return $startYear . ' - ' . $currentYear;
    }

    /**
     * Get the version from composer.lock for a given package.
     * 
     * @param string $packageName The name of the package.
     * @return string|null The package version or null if not found.
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
}
