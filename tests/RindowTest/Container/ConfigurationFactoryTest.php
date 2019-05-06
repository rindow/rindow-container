<?php
namespace RindowTest\Container\ConfigurationFactoryTest;

use PHPUnit\Framework\TestCase;
use stdClass;
use Rindow\Container\ModuleManager;
use Rindow\Stdlib\Entity\AbstractEntity;

// Test Target Classes
use Rindow\Container\ConfigurationFactory;
use Rindow\Container\Container;

class TestClass extends AbstractEntity
{
	public $config;
	public $direct;
}

class Test extends TestCase
{

    public static function setUpBeforeClass()
    {
    }

    public function setUp()
    {
    }

    public function testNormal()
    {
    	$config = array(
    		'test' => array(
    			'nest1' => 'value',
    		),
    	);
    	$container = new Container();
    	$container->setInstance('config',$config);

    	$result = ConfigurationFactory::factory($container,'test::nest1');
    	$this->assertEquals('value', $result);
    	$result = ConfigurationFactory::factory($container,'test');
    	$this->assertEquals(array('nest1'=>'value'), $result);
    	$result = ConfigurationFactory::factory($container,'none');
    	$this->assertNull($result);

    	$result = ConfigurationFactory::factory($container,'dummy',array('config'=>'test::nest1'));
    	$this->assertEquals('value', $result);
    	$result = ConfigurationFactory::factory($container,'dummy',array('config'=>'test'));
    	$this->assertEquals(array('nest1'=>'value'), $result);
    	$result = ConfigurationFactory::factory($container,'dummy',array('config'=>'none'));
    	$this->assertNull($result);
    }

    public function testInModuleManager()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                ),
                'enableCache'=>false,
            ),
            'container' => array(
                'components' => array(
                    'TestConfigObject' => array(
                        'factory' => 'Rindow\Container\ConfigurationFactory::factory',
                        'factory_args' => array('config'=>'test::nest1'),
                        'proxy' => 'disable',
                    ),
                ),
            ),
    		'test' => array(
    			'nest1' => 'value',
    		),

    	);
    	$mm = new ModuleManager($config);

        $result = $mm->getServiceLocator()->get('TestConfigObject');
    	$this->assertEquals('value', $result);
    }


    public function testInConfigInjectTypePrefix()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                ),
                'enableCache'=>false,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\\TestClass' => array(
                    	'properties' => array(
                    		'config' => array('config'=>'test'),
                    		'direct' => array('value' =>'test'),
                    	),
                    ),
                ),
            ),
    		'test' => array(
    			'nest1' => 'value',
    		),

    	);
    	$mm = new ModuleManager($config);

        $result = $mm->getServiceLocator()->get(__NAMESPACE__.'\\TestClass');
    	$this->assertEquals(array('nest1'=>'value'), $result->config);
    	$this->assertEquals('test', $result->direct);
    }
}
