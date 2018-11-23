<?php
namespace RindowTest\Container\AnnotationAliasTest;

use PHPUnit\Framework\TestCase;

use Rindow\Container\ModuleManager;
use Interop\Lenient\Container\Annotation\Named;
use Interop\Lenient\Container\Annotation\Inject;

/**
 * @Named
 */
class TestRootEntity
{
    /**
    * @Inject({@Named("RindowTest\Container\AnnotationAliasTest\Prop1Entity")})
    */
    public $prop1;

    public function setProp1($prop1)
    {
        $this->prop1 = $prop1;
    }
}

/**
 * @Named
 */
class Prop1Entity
{
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
    				'Rindow\\Container\\Module' => true,
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
    	$obj = $container->get(__NAMESPACE__.'\\TestRootEntity');
    	$this->assertInstanceOf(__NAMESPACE__.'\\TestRootEntity',$obj);
    	$this->assertInstanceOf(__NAMESPACE__.'\\Prop1Entity',$obj->prop1);
    }
}