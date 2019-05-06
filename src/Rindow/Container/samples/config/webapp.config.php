<?php
return array(
    'module_manager' => array(
        'version' => 2,
        'modules' => array(
            'Rindow\Container\Module'    => true,
            'Rindow\Aop\Module'          => true,
            //
            // Some framework modules
            // 'Rindow\......\Module'          => true,
            //  ....
            //  ....
            // Your modules
            'Acme\MyApp\Module'          => true,
        ),
        //'imports' => array(
        //    __DIR__.'/local' => '@\.php$@',
        //),
        // You usually need an annotation manager.
        'annotation_manager' => true,
        // 
        // Module you want to execute automatically.
        'autorun' => 'Rindow\Web\Mvc\Module',
    ),
    'cache' => array(
        'filePath'   => __DIR__.'/../cache',
        // The CacheFactory defaults
        //'fileCache' => array(
        //    'class' => 'Rindow\\Stdlib\\Cache\\SimpleCache\\FileCache',
        //    'path'   => null,
        //),
        //'memCache' => array(
        //    // The CacheFactory defaults
        //    'class' => 'Rindow\\Stdlib\\Cache\\SimpleCache\\ApcCache',
        //    'path'   => '',
        //),
        //'configCache' => array(
        //    // The CacheFactory defaults
        //    'path'   => '',
        //    'enableMemCache'  => true,
        //    'enableFileCache' => true,
        //    'forceFileCache'  => false,
        //    'apcTimeOut'      => 300,
        //),
    ),
    //'container' => array(
    //    ...
    //    ...
    //    ...
    //),
);

