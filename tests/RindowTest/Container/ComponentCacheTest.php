<?php
namespace RindowTest\Container\ComponentCacheTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\ModuleManager;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;
use Rindow\Container\Container;
use Rindow\Container\Exception\DomainException as ContainerException;

class Piece
{
}
class Bag
{
    protected $piece;

    public function setPiece($piece)
    {
        $this->piece = $piece;
    }

    public function getPiece()
    {
        return $this->piece;
    }
}
class BagModule
{
    public function getConfig()
    {
        return array(
            'container'=>array(
                'components'=>array(
                    __NAMESPACE__.'\Bag'=>array(
                        'properties'=>array(
                            'piece'=>array('ref'=>__NAMESPACE__.'\Piece'),
                        ),
                    ),
                    __NAMESPACE__.'\Piece'=>array(
                    ),
                ),
            ),
        );
    }
}

class Test extends TestCase
{
    protected static $skip = false;
    protected static $backupEnableMemCache;
    protected static $backupEnableFileCache;
    protected static $backupForceFileCache;
    protected static $backupFileCachePath;

    public static function setUpBeforeClass()
    {
        //self::$backupEnableMemCache  = CacheFactory::$enableMemCache;
        //self::$backupEnableFileCache = CacheFactory::$enableFileCache;
        //self::$backupForceFileCache  = CacheFactory::$forceFileCache;
        //self::$backupFileCachePath   = CacheFactory::$fileCachePath;

        if(!extension_loaded('apcu') && !extension_loaded('apc'))
            self::$skip = 'Neither apc nor apcu is found';
    }

    public static function tearDownAfterClass()
    {
        //CacheFactory::$enableMemCache  = self::$backupEnableMemCache ;
        //CacheFactory::$enableFileCache = self::$backupEnableFileCache;
        //CacheFactory::$forceFileCache  = self::$backupForceFileCache ;
        //CacheFactory::$fileCachePath   = self::$backupFileCachePath  ;
    }

    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped(self::$skip);
            return;
        }
        //usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        //CacheFactory::clearCache();
        //usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
    }

    public function getConfig()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    __NAMESPACE__.'\BagModule' => true,
                ),
                //'enableCache' => true, // Default=true
            ),
            'cache' => array(
                //'fileCachePath'   => __DIR__.'/../cache',
                'configCache' => array(
                    'enableMemCache'  => true,
                    'enableFileCache' => true,
                    'forceFileCache'  => false,
                ),
                //'apcTimeOut'      => 20,
                'memCache' => array(
                    'class' => 'Rindow\Stdlib\Cache\SimpleCache\ArrayCache',
                ),
                'fileCache' => array(
                    'class' => 'Rindow\Stdlib\Cache\SimpleCache\ArrayCache',
                ),
            ),
            'container' => array(
                'component_paths' => array(
                    __DIR__=>false,
                ),
            ),

        );
        return $config;
    }

    //public function apc_delete($key)
    //{
    //    if(extension_loaded('apcu'))
    //        apcu_delete($key);
    //    elseif(extension_loaded('apc'))
    //        apcu_delete($key);
    //    else
    //        throw new \Exception("apc not found");
    //}

    public function testExpiredComponentDefinition()
    {
        $config = $this->getConfig();
        $cacheFactory = new ConfigCacheFactory($config['cache']);
        $memCache = $cacheFactory->create('')->getPrimary();
        $this->assertNull($cacheFactory->create('')->getSecondary());
        $mm = new ModuleManager($config);
        $mm->setConfigCacheFactory($cacheFactory);
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());

        //var_dump($memCache->getAllKeys());
        $this->assertTrue($memCache->has('Rindow/Container/Container/ComponentDefinitionManager/component/'.__NAMESPACE__.'\Bag'));
        $memCache->delete('Rindow/Container/Container/ComponentDefinitionManager/component/'.__NAMESPACE__.'\Bag');

        $mm = new ModuleManager($this->getConfig());
        $mm->setConfigCacheFactory($cacheFactory);
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());
        $this->assertTrue($memCache->has('Rindow/Container/Container/ComponentDefinitionManager/component/'.__NAMESPACE__.'\Bag'));
    }

    public function testExpiredConfig()
    {
        $config = $this->getConfig();
        $cacheFactory = new ConfigCacheFactory($config['cache']);
        $memCache = $cacheFactory->create('')->getPrimary();
        $this->assertNull($cacheFactory->create('')->getSecondary());
        $mm = new ModuleManager($config);
        $mm->setConfigCacheFactory($cacheFactory);
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());

        //var_dump($memCache->getAllKeys());
        $this->assertTrue($memCache->has('Rindow/Container/ModuleManager/config/staticConfig'));
        $memCache->delete('Rindow/Container/ModuleManager/config/staticConfig');

        $mm = new ModuleManager($this->getConfig());
        $mm->setConfigCacheFactory($cacheFactory);
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());
        $this->assertTrue($memCache->has('Rindow/Container/ModuleManager/config/staticConfig'));
    }

    public function testExpiredScannedComponentFlag()
    {
        $config = $this->getConfig();
        $cacheFactory = new ConfigCacheFactory($config['cache']);
        $memCache = $cacheFactory->create('')->getPrimary();
        $this->assertNull($cacheFactory->create('')->getSecondary());
        $mm = new ModuleManager($config);
        $mm->setConfigCacheFactory($cacheFactory);
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());

        //var_dump($memCache->getAllKeys());
        $this->assertTrue($memCache->has('Rindow/Container/Container/ComponentDefinitionManager/scannedComponent/__INITIALIZED__'));
        $memCache->delete('Rindow/Container/Container/ComponentDefinitionManager/scannedComponent/__INITIALIZED__');

        $mm = new ModuleManager($this->getConfig());
        $mm->setConfigCacheFactory($cacheFactory);
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());
        $this->assertTrue($memCache->has('Rindow/Container/Container/ComponentDefinitionManager/scannedComponent/__INITIALIZED__'));
    }

    public function testMultiContainer()
    {
        $cacheConfig = $this->getConfig();
        $cacheFactory = new ConfigCacheFactory($cacheConfig['cache']);
        $config = array(
            'components' => array(
                'a' => array(
                    'class' => __NAMESPACE__.'\Piece',
                ),
            ),
        );
        $container1 = new Container(null,null,null,null,'same-name',$cacheFactory);   // has "a"
        $container1->setConfig($config);
        $container2 = new Container(null,null,null,null,'same-name',$cacheFactory);   // empty
        // Cacheing 'Not have "a"' at first in "container2".
        // And then try to get "a" in "container1" from cache.
        $container2->setParentManager($container1);
        try {
            $notfound = false;
            $object = $container2->get('a');
        } catch(ContainerException $e) {
            $notfound = true;
        }
        $this->assertTrue($notfound);


        $container1 = new Container(null,null,null,null,'cache1-name',$cacheFactory);   // has "a"
        $container1->setConfig($config);
        $container2 = new Container(null,null,null,null,'cache2-name',$cacheFactory);   // empty
        // Cacheing 'Not have "a"' at first in "container2".
        // And then try to get "a" in "container1" from other cache.
        $container2->setParentManager($container1);
        try {
            $notfound = false;
            $object = $container2->get('a');
        } catch(ContainerException $e) {
            $notfound = true;
        }
        $this->assertFalse($notfound);
    }
}