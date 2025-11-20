<?php

declare(strict_types=1);

namespace UtilityKit\Utility;

final class DateFormatter
{
    /**
     * Get the copyright year string (e.g., "2020-2024").
     */
    public static function copyrightRange(int $startYear, string $format = '%s-%s'): string
    {
        $currentYear = (int) date('Y');

        return $startYear < $currentYear
            ? sprintf($format, $startYear, $currentYear)
            : (string) $startYear;
    }
}