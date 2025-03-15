<?php

declare(strict_types=1);

namespace UtilityKit\Utility;

abstract class Common
{
    /**
     * Get current year
     * 
     * @param int $startYear
     * @return string
     */
    public static function getCopyrigthYear(int $startYear): string
    {
        $currentYear = date('Y');
        if ($startYear === $currentYear) {
            return $currentYear;
        }
        return $startYear . ' - ' . $currentYear;
    }

    /**
     * Get version from composer.lock
     * 
     * @param string $pakage
     * @param bool $cache
     * @return string|null
     */
    public static function packageVersion(string $pakage): ?string
    {
        $composerLock = json_decode(file_get_contents(ROOT . DS . 'composer.lock'), true);
        foreach ($composerLock['packages'] as $package) {
            if ($package['name'] === $pakage) {
                return $package['version'];
            }
        }

        return null;
    }
}
