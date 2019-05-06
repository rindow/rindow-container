<?php
include __DIR__.'/../vendor/autoload.php';

$config = require __DIR__.'/../config/webapp.config.php';
$app = new Rindow\Container\ModuleManager($config);
$app->run();
