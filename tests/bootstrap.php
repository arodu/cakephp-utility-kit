<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for UtilityKit.
 */
$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);

chdir($root);

require_once $root . '/vendor/autoload.php';

require_once $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';

use Cake\TestSuite\ConnectionHelper;
use Cake\TestSuite\Fixture\SchemaLoader;

(new SchemaLoader())->loadInternalFile($root . '/tests/schema.php', 'test');
ConnectionHelper::addTestAliases();
