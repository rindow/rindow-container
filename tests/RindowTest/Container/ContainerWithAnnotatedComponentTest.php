<?php
namespace RindowTest\Container\ContainerWithAnnotatedComponentTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\ModuleManager;
use Rindow\Container\Annotation\Named;
use Rindow\Container\Annotation\Inject;

/**
 * @Named
 */
class Normal
{
    protected $prop1;
    
    public function __construct($foo=null)
    {
        $this->foo = $foo;
    }

    public function setProp1($prop1)
    {
        $this->prop1 = $prop1;
    }
}

class Test extends TestCase
{
	protected static $RINDOW_TEST_RESOURCES;

    public static function setUpBeforeClass()
    {
	 	//self::$RINDOW_TEST_RESOURCES= __DIR__.'/../../resources/AcmeTest/AnnotatedComponent';
	 	self::$RINDOW_TEST_RESOURCES= __DIR__;
    }

    public function setUp()
    {
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
    }

    public function getConfig()
    {
    	$config = array(
    		'module_manager' => array(
    			'modules' => array(
                    'Rindow\\Container\\Module'=>true,
    			),
		        'annotation_manager' => true,
    		),
    		'container' => array(
    			'component_paths' => array(
    				self::$RINDOW_TEST_RESOURCES => true,
    			),
    		),
    	);
    	return $config;
    }

    public function test()
    {
    	$mm = new ModuleManager($this->getConfig());
    	$container = $mm->getServiceLocator();
    	$obj = $container->get(__NAMESPACE__.'\\Normal');
    	$this->assertInstanceOf(__NAMESPACE__.'\\Normal',$obj);
    }
}