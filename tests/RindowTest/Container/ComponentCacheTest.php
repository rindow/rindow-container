<?php
namespace RindowTest\Container\ComponentCacheTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\ModuleManager;
use Rindow\Stdlib\Cache\CacheFactory;
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
    protected static $backupEnableMemCache;
    protected static $backupEnableFileCache;
    protected static $backupForceFileCache;
    protected static $backupFileCachePath;

    public static function setUpBeforeClass()
    {
        self::$backupEnableMemCache  = CacheFactory::$enableMemCache;
        self::$backupEnableFileCache = CacheFactory::$enableFileCache;
        self::$backupForceFileCache  = CacheFactory::$forceFileCache;
        self::$backupFileCachePath   = CacheFactory::$fileCachePath;
    }

    public static function tearDownAfterClass()
    {
        CacheFactory::$enableMemCache  = self::$backupEnableMemCache ;
        CacheFactory::$enableFileCache = self::$backupEnableFileCache;
        CacheFactory::$forceFileCache  = self::$backupForceFileCache ;
        CacheFactory::$fileCachePath   = self::$backupFileCachePath  ;
    }

    public function setUp()
    {
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
    }

    public function getConfig()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    __NAMESPACE__.'\BagModule' => true,
                ),
            ),
            'cache' => array(
                //'fileCachePath'   => __DIR__.'/../cache',
                'enableMemCache'  => true,
                'enableFileCache' => true,
                'forceFileCache'  => false,
                //'apcTimeOut'      => 20,
            ),

        );
        return $config;
     }

    public function testExpiredComponentDefinition()
    {
        $mm = new ModuleManager($this->getConfig());
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());

        CacheFactory::$caches =array();
        apc_delete('Rindow\Container\ComponentDefinitionManager/\/component/RindowTest\Container\ContainerCacheTest\Bag');

        $mm = new ModuleManager($this->getConfig());
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());
    }

    public function testExpiredConfig()
    {
        $mm = new ModuleManager($this->getConfig());
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());

        CacheFactory::$caches =array();
        apc_delete('Rindow\Container\ModuleManager/\/config/staticConfig');

        $mm = new ModuleManager($this->getConfig());
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());
    }

    public function testExpiredLoadedFlag()
    {
        $mm = new ModuleManager($this->getConfig());
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());

        CacheFactory::$caches =array();
        apc_delete('Rindow\Container\ComponentDefinitionManager/\/namedComponent/__COMPONENTS_ARE_LOADED__');

        $mm = new ModuleManager($this->getConfig());
        $sl = $mm->getServiceLocator();
        $bag = $sl->get(__NAMESPACE__.'\Bag');
        $this->assertInstanceof(__NAMESPACE__.'\Piece',$bag->getPiece());
    }

    public function testMultiContainer()
    {
        $config = array(
            'components' => array(
                'a' => array(
                    'class' => __NAMESPACE__.'\Piece',
                ),
            ),
        );
        $container1 = new Container(null,null,null,null,'same-name');   // has "a"
        $container1->setConfig($config);
        $container2 = new Container(null,null,null,null,'same-name');   // empty
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


        $container1 = new Container(null,null,null,null,'cache1-name');   // has "a"
        $container1->setConfig($config);
        $container2 = new Container(null,null,null,null,'cache2-name');   // empty
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