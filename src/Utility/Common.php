<?php
declare(strict_types=1);

namespace UtilityKit\Utility;

abstract class Common
{
    /**
     * Get the version of the package
     *
     * @param string $package
     * @return string
     */
    public static function getPackageVersion(string $package): ?string
    {
        $composer = json_decode(file_get_contents(ROOT . DS . 'composer.lock'), true);
        foreach ($composer['packages'] as $item) {
            if ($item['name'] === $package) {
                return $item['version'];
            }
        }

        return null;
    }

    /**
     * Get the current year
     *
     * @param int|null $year
     * @return string
     */
    public static function getCopyrigthYear(?int $year = null): string
    {
        $year = $year ?? date('Y');
        $currentYear = date('Y');
        if ($year === $currentYear) {
            return $year;
        }

        return $year . ' - ' . $currentYear;
    }
}