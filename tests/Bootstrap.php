<?php
ini_set('short_open_tag', '1');

date_default_timezone_set('UTC');
#ini_set('short_open_tag',true);
include 'init_autoloader.php';
$loader->add('AcmeTest\\DiContainer\\', __DIR__ . '/resources');
$loader->add('AcmeTest\\Module1\\', __DIR__ . '/resources');
$loader->add('AcmeTest\\Module2\\', __DIR__ . '/resources');
define('RINDOW_TEST_CACHE',     __DIR__.'/cache');
define('RINDOW_TEST_CLEAR_CACHE_INTERVAL',100000);
Rindow\Stdlib\Cache\CacheFactory::$fileCachePath = RINDOW_TEST_CACHE;
Rindow\Stdlib\Cache\CacheFactory::$enableMemCache = true;
Rindow\Stdlib\Cache\CacheFactory::$enableFileCache = false;
//Rindow\Stdlib\Cache\CacheFactory::$notRegister = true;
Rindow\Stdlib\Cache\CacheFactory::clearCache();
if(!class_exists('PHPUnit\Framework\TestCase')) {
    include __DIR__.'/travis/patch55.php';
}
