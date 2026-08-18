<?php
declare(strict_types=1);

namespace UtilityKit\Utility;

use Throwable;

final class GitInfo
{
    /**
     * Get the current Git repository status.
     *
     * @return array An array containing 'branch', 'commit_id', and 'commit_date'.
     */
    public static function getRepositoryStatus(): array
    {
        $info = [
            'branch' => null,
            'commit_id' => null,
            'commit_date' => null,
        ];

        if (!function_exists('shell_exec') || !is_dir(ROOT . DS . '.git')) {
            return $info;
        }

        try {
            $output = shell_exec('cd ' . escapeshellarg(ROOT) . ' && git log -1 --format="%h|%ci|%D"');

            if ($output) {
                $parts = explode('|', trim($output));

                if (count($parts) >= 3) {
                    $info['commit_id'] = $parts[0];
                    $info['commit_date'] = $parts[1];
                    $refs = explode(',', $parts[2]);
                    $info['branch'] = trim(str_replace('HEAD ->', '', $refs[0]));
                }
            }
        } catch (Throwable $e) {
        }

        return $info;
    }
}
