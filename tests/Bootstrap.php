<?php
ini_set('short_open_tag', '1');

date_default_timezone_set('UTC');
#ini_set('short_open_tag',true);
include 'init_autoloader.php';
$loader->add('AcmeTest\\DiContainer\\', __DIR__ . '/resources');
$loader->add('AcmeTest\\Module1\\', __DIR__ . '/resources');
$loader->add('AcmeTest\\Module2\\', __DIR__ . '/resources');

if(!class_exists('PHPUnit\Framework\TestCase')) {
    include __DIR__.'/travis/patch55.php';
}
