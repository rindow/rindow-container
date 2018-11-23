<?php
namespace RindowTest\Container\ContainerTest;

use PHPUnit\Framework\TestCase;
use Rindow\Stdlib\Entity\PropertyAccessPolicy;
use Rindow\Stdlib\Entity\AbstractEntity;
use Rindow\Annotation\AnnotationManager;

use Rindow\Container\ServiceLocator;

// Test Target Classes
use Rindow\Container\Container;
use Rindow\Container\Annotation\Inject;
use Rindow\Container\Annotation\Named;
use Rindow\Container\Annotation\NamedConfig;
use Rindow\Container\Annotation\NamedIn;
use Rindow\Container\Annotation\Scope;
use Rindow\Container\Annotation\PostConstruct;

interface Param0Interface
{
}
interface Param1Interface
{
}

class Param0 implements Param0Interface
{
}

class Param1
{
    public function __construct(Param0 $arg1)
    {
        $this->arg1 = $arg1;
    }

    public function getArg1()
    {
        return $this->arg1;
    }
}

class Param1Dash implements Param1Interface
{
    public function __construct(Param0Interface $arg1)
    {
        $this->arg1 = $arg1;
    }

    public function getArg1()
    {
        return $this->arg1;
    }
}

class Param2
{
    public function __construct($arg1)
    {
        $this->arg1 = $arg1;
    }

    public function getArg1()
    {
        return $this->arg1;
    }
}

class Param3
{
    public function __construct(Param1 $arg1,Param2 $arg2)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }

    public function getArg1()
    {
        return $this->arg1;
    }

    public function getArg2()
    {
        return $this->arg2;
    }
}

class Param3Dash
{
    public function __construct(Param1Interface $arg1,Param2 $arg2)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }

    public function getArg1()
    {
        return $this->arg1;
    }

    public function getArg2()
    {
        return $this->arg2;
    }
}

interface CombiServiceInterface {}
class CombiService implements CombiServiceInterface
{
    protected $data;

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setArgs($args)
    {
        $this->args = $args;
    }

    public function getArgs()
    {
        return $this->args;
    }
}
class CombiServiceFactory
{
    public static function factory(/*ServiceLocator*/ $sm,$componentName=null,$args=null)
    {
        $instance = new CombiService();
        $instance->setData('Created from Factory');
        $instance->setName($componentName);
        $instance->setArgs($args);
        return $instance;
    }
}

class CombiDiClass1
{
    protected $service;

    public function __construct(CombiServiceInterface $service)
    {
        $this->service = $service;
    }

    public function getService()
    {
        return $this->service;
    }
}

class CombiDiClass2
{
    protected $service0;
    protected $service1;

    public function __construct(CombiServiceInterface $service0, CombiDiClass1 $service1)
    {
        $this->service0 = $service0;
        $this->service1 = $service1;
    }

    public function getService0()
    {
        return $this->service0;
    }

    public function getService1()
    {
        return $this->service1;
    }
}

class SetterInjectionClass
{
    protected $arg0;
    protected $arg1;
    public function setArg0(Param0Interface $arg0)
    {
        $this->arg0 =$arg0;
    }
    public function setArg1($arg1)
    {
        $this->arg1 =$arg1;
    }
    public function getArg0()
    {
        return $this->arg0;
    }
    public function getArg1()
    {
        return $this->arg1;
    }
}

class PropertyInjectionClass extends AbstractEntity
{
    protected $arg0;
    protected $arg1;
}

class DefaultValueClass
{
    const VALUE100 = 100;
    protected $arg0;
    protected $arg1;
    protected $arg2;

    public function __construct(Param0Interface $arg0=null,$arg1=self::VALUE100)
    {
        $this->arg0 = $arg0;
        $this->arg1 = $arg1;
    }
    public function setArg2(Param0Interface $arg2=null)
    {
        $this->arg2 = $arg2;
    }
    public function getArg0()
    {
        return $this->arg0;
    }
    public function getArg1()
    {
        return $this->arg1;
    }
}

class FieldAnnotationInjection extends AbstractEntity
{
    /**
    * @Inject({@Named("RindowTest\Container\ContainerTest\Param0")})
    */
    public $arg0;
}

class PropertyAccessInjection implements PropertyAccessPolicy
{
    /**
    * @Inject({@Named("RindowTest\Container\ContainerTest\Param0")})
    */
    public $arg0;
}

class MultiArgInjection
{
    /**
    * @Inject
    */
    public function setArguments(Param0 $arg0,Param1 $arg1)
    {
        $this->arg0 = $arg0;
        $this->arg1 = $arg1;
    }
    public function getArg0()
    {
        return $this->arg0;
    }
    public function getArg1()
    {
        return $this->arg1;
    }
}

/**
* @Scope("prototype")
*/
class PrototypeScope
{
}

/**
* @Scope("singleton")
*/
class SingletonScope
{
}

class PostConstructClass
{
    protected $initialized;

    protected $arg0;

    /**
    * @Inject
    */
    public function setArg0($arg0=123)
    {
        $this->arg0 = $arg0;
        $this->initialized = null;
    }
    public function getArg0()
    {
        return $this->arg0;
    }

    /**
    * @PostConstruct
    */
    public function init()
    {
        $this->initialized = true;
    }
    public function is_initialized()
    {
        return $this->initialized;
    }
}

class TestRecursionA
{
    public $obj;

    public function setObj($obj)
    {
        $this->obj = $obj;
    }
}

class TestRecursionB
{
    public $obj;

    public function setObj($obj)
    {
        $this->obj = $obj;
    }
}

class InjectNamedConfig
{
    /**
    * @Inject({@NamedConfig("test::named::config::value")})
     */
    protected $testValue;

    protected $testSetter;

    public function setTestValue($testValue)
    {
        $this->testValue = $testValue;
    }

    public function getTestValue()
    {
        return $this->testValue;
    }

    /**
    * @Inject({@NamedConfig(parameter="testSetter",value="test::named::config::setter")})
    */
    public function setTestSetter($testSetter)
    {
        $this->testSetter = $testSetter;
    }
    public function getTestSetter()
    {
        return $this->testSetter;
    }
}

class InjectNamedIn
{
    /**
    * @Inject({@NamedIn("test::named::config::value")})
     */
    protected $testValue;

    protected $testSetter;

    public function setTestValue($testValue)
    {
        $this->testValue = $testValue;
    }

    public function getTestValue()
    {
        return $this->testValue;
    }

    /**
    * @Inject({@NamedIn(parameter="testSetter",value="test::named::config::setter")})
    */
    public function setTestSetter($testSetter)
    {
        $this->testSetter = $testSetter;
    }
    public function getTestSetter()
    {
        return $this->testSetter;
    }
}
class InjectNamedInConfig
{
    protected $value;
    public function setValue($value)
    {
        $this->value = $value;
    }
    public function getValue()
    {
        return $this->value;
    }
}

class Test extends TestCase
{
    static $RINDOW_TEST_RESOURCES;
    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/../../resources';
    }

    public static function tearDownAfterClass()
    {
    }

    public function setUp()
    {
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
    }

    public function testConstructor()
    {
        $di = new Container();

        $cm = $di->getComponentManager();
        $this->assertEquals('Rindow\Container\ComponentDefinitionManager', get_class($cm));
        $dm = $di->getDeclarationManager();
        $this->assertEquals('Rindow\Container\DeclarationManager', get_class($dm));
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage Undefined component.: RindowTest\Container\ContainerTest\NonDefinedComponent
     */
    public function testGetNone()
    {
        $im = new Container();
        $this->assertFalse($im->has(__NAMESPACE__.'\NonDefinedComponent'));
        $i0 = $im->get(__NAMESPACE__.'\NonDefinedComponent');
        print_r($i0);
    }

    public function testConstructorArg0()
    {
        $config = array(
            'components'=>array(
                __NAMESPACE__.'\Param0'=>array(
                ),
            ),
        );
        $di = new Container($config);
        $i0 = $di->get(__NAMESPACE__.'\Param0');

        $this->assertEquals(__NAMESPACE__.'\Param0', get_class($i0));
    }

    public function testConstructorArg1Normal()
    {
        $config = array(
            'components'=>array(
                __NAMESPACE__.'\Param0'=>array(
                ),
                __NAMESPACE__.'\Param1'=>array(
                ),
            ),
        );
        $di = new Container($config);

        $i1 = $di->get(__NAMESPACE__.'\Param1');
        $this->assertEquals(__NAMESPACE__.'\Param1', get_class($i1));

        $i0 = $i1->getArg1();
        $this->assertEquals(__NAMESPACE__.'\Param0', get_class($i0));
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage Undefined a specified class or instance for parameter:RindowTest\Container\ContainerTest\Param2::__construct( .. $arg1 .. )
     */
    public function testConstructorArg1NonDef()
    {
        $config = array(
            'components'=>array(
                __NAMESPACE__.'\Param2'=>array(
                ),
            ),
        );
        $di = new Container($config);

        $i1 = $di->get(__NAMESPACE__.'\Param2');
        $this->assertEquals(__NAMESPACE__.'\Param2', get_class($i1));
    }

    public function testConstructorArg1NonDefAddArg()
    {
        $di = new Container();
        $di->getComponentManager()
            ->getComponent(__NAMESPACE__.'\Param2',true)
            ->addConstructorArgWithValue('arg1','xyz');
        $i2 = $di->get(__NAMESPACE__.'\Param2');
        $this->assertEquals(__NAMESPACE__.'\Param2', get_class($i2));

        $this->assertEquals('xyz', $i2->getArg1());
    }

    public function testConstructorArg1NonDefAddArgNullValue()
    {
        $di = new Container();
        $di->getComponentManager()
            ->getComponent(__NAMESPACE__.'\Param2',true)
            ->addConstructorArgWithValue('arg1',null);
        $i2 = $di->get(__NAMESPACE__.'\Param2');
        $this->assertEquals(__NAMESPACE__.'\Param2', get_class($i2));

        $this->assertNull($i2->getArg1());
    }

    public function testConstructorArg2NonDefAddArg()
    {
        $config = array(
            'components'=>array(
                __NAMESPACE__.'\Param0'=>array(
                ),
                __NAMESPACE__.'\Param1'=>array(
                ),
                __NAMESPACE__.'\Param2'=>array(
                ),
                __NAMESPACE__.'\Param3'=>array(
                ),
            ),
        );
        $di = new Container($config);
        $di->getComponentManager()
            ->getComponent(__NAMESPACE__.'\Param2',true)
            ->addConstructorArgWithValue('arg1','xyz');
        $i3 = $di->get(__NAMESPACE__.'\Param3');
        $this->assertEquals(__NAMESPACE__.'\Param3', get_class($i3));

        $i1 = $i3->getArg1();
        $this->assertEquals(__NAMESPACE__.'\Param1', get_class($i1));
        $i2 = $i3->getArg2();
        $this->assertEquals(__NAMESPACE__.'\Param2', get_class($i2));
        $s = $i2->getArg1();
        $this->assertEquals('xyz', $s);
        $i0 = $i1->getArg1();
        $this->assertEquals(__NAMESPACE__.'\Param0', get_class($i0));
    }

    /**
     * @expectedException        Rindow\Container\Exception\InvalidArgumentException
     * @expectedExceptionMessage Class name must be string
     */
    public function testConstructorIlligalName()
    {
        $di = new Container();
        $im = $di->get(array());
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage Undefined component.: RindowTest\Container\ContainerTest\Param0Interface
     */
    public function testConstructorArg1UnhandleInterface()
    {
        $config = array(
            'components'=>array(
                __NAMESPACE__.'\Param1Dash'=>array(
                ),
            ),
        );
        $di = new Container($config);
        $i1 = $di->get(__NAMESPACE__.'\Param1Dash');
    }

    public function testConstructorArg1HandleInterfaceWithAlias()
    {
        $config = array(
            'components'=>array(
                __NAMESPACE__.'\Param1Dash'=>array(
                ),
                __NAMESPACE__.'\Param0'=>array(
                ),
            ),
        );
        $di = new Container($config);
        $di->getComponentManager()
            ->addAlias(__NAMESPACE__.'\Param0Interface', __NAMESPACE__.'\Param0');
        $i1 = $di->get(__NAMESPACE__.'\Param1Dash');
        $this->assertEquals(__NAMESPACE__.'\Param1Dash', get_class($i1));
        $this->assertEquals(__NAMESPACE__.'\Param0', get_class($i1->getArg1()));
    }

    public function testConstructorArg1HandleInterfaceWithReference()
    {
        $config = array(
            'components'=>array(
                __NAMESPACE__.'\Param1Dash'=>array(
                ),
                __NAMESPACE__.'\Param0'=>array(
                ),
            ),
        );
        $di = new Container($config);
        $di->getComponentManager()
            ->getComponent(__NAMESPACE__.'\Param1Dash',true)
            ->addConstructorArgWithReference('arg1',__NAMESPACE__.'\Param0');
        $i1 = $di->get(__NAMESPACE__.'\Param1Dash');
        $this->assertEquals(__NAMESPACE__.'\Param1Dash', get_class($i1));
        $this->assertEquals(__NAMESPACE__.'\Param0', get_class($i1->getArg1()));
    }

    public function testConstructorArg3HandleInterface()
    {
        $config = array(
            'components'=>array(
                __NAMESPACE__.'\Param0'=>array(
                ),
                __NAMESPACE__.'\Param1Dash'=>array(
                ),
                __NAMESPACE__.'\Param2'=>array(
                ),
                __NAMESPACE__.'\Param3Dash'=>array(
                ),
            ),
        );
        $di = new Container($config);
        $di->getComponentManager()
            ->addAlias(__NAMESPACE__.'\Param0Interface', __NAMESPACE__.'\Param0');
        $di->getComponentManager()
            ->addAlias(__NAMESPACE__.'\Param1Interface', __NAMESPACE__.'\Param1Dash');
        $di->getComponentManager()
            ->getComponent(__NAMESPACE__.'\Param2',true)
            ->addConstructorArgWithValue('arg1','xyz');
        $i3 = $di->get(__NAMESPACE__.'\Param3Dash');
        $this->assertEquals(__NAMESPACE__.'\Param3Dash', get_class($i3));
        $this->assertEquals(__NAMESPACE__.'\Param1Dash', get_class($i3->getArg1()));
        $this->assertEquals(__NAMESPACE__.'\Param2', get_class($i3->getArg2()));
        $this->assertEquals(__NAMESPACE__.'\Param0', get_class($i3->getArg1()->getArg1()));
    }

    public function testLoad()
    {
        $config = array(
            'runtime_complie' => false,
            'declarations' => array(
                array(
                    'class' => __NAMESPACE__.'\Param0',
                    'constructor' => null,
                    'injects' => array(),
                ),
                array(
                    'class' => __NAMESPACE__.'\Param1Dash',
                    'constructor' => '__construct',
                    'injects' => array(
                        '__construct' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param0Interface'),
                        ),
                    ),
                ),
                array(
                    'class' => __NAMESPACE__.'\Param2',
                    'constructor' => '__construct',
                    'injects' => array(
                        '__construct' => array(
                            'arg1' => array(),
                        ),
                    ),
                ),
                array(
                    'class' => __NAMESPACE__.'\Param3Dash',
                    'constructor' => '__construct',
                    'injects' => array(
                        '__construct' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param1Interface'),
                            'arg2' => array('ref'=>__NAMESPACE__.'\Param2'),
                        ),
                    ),
                ),
            ),
            'aliases' => array(
                __NAMESPACE__.'\Param1Interface' => __NAMESPACE__.'\Param1Dash',
            ),
            'resources' => array(
                self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Resources/config/service.xml',
            ),
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                ),
                __NAMESPACE__.'\Param1Dash' => array(
                    'constructor_args' => array(
                        'arg1' => array('ref' => __NAMESPACE__.'\Param0'),
                    ),
                ),
                __NAMESPACE__.'\Param2' => array(
                    'constructor_args' => array(
                        'arg1' => array('value' => 'xyz'),
                    ),
                ),
                __NAMESPACE__.'\Param3Dash' => array(
                ),
            ),
        );

        $di = new Container($config);

        $i3 = $di->get(__NAMESPACE__.'\Param3Dash');
        $this->assertEquals(__NAMESPACE__.'\Param3Dash', get_class($i3));
        $this->assertEquals(__NAMESPACE__.'\Param1Dash', get_class($i3->getArg1()));
        $this->assertEquals(__NAMESPACE__.'\Param2', get_class($i3->getArg2()));
        $this->assertEquals('xyz', $i3->getArg2()->getArg1());
        $this->assertEquals(__NAMESPACE__.'\Param0', get_class($i3->getArg1()->getArg1()));
    }

    public function testFactory()
    {
        $config = array (
            'components' => array(
                __NAMESPACE__.'\CombiService' => array(
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                ),
                'fooCombiService' => array(
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                ),
                'foo2CombiService' => array(
                    'class' => __NAMESPACE__.'\CombiService',
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                ),
            ),
        );
        $di = new Container($config);
        $i0 = $di->get(__NAMESPACE__.'\CombiService');
        $this->assertEquals(__NAMESPACE__.'\CombiService', get_class($i0));
        $data = $i0->getData();
        $this->assertEquals('Created from Factory', $data);
        $this->assertEquals(__NAMESPACE__.'\CombiService',$i0->getName());

        $i1 = $di->get('fooCombiService');
        $this->assertEquals(__NAMESPACE__.'\CombiService', get_class($i1));
        $data = $i1->getData();
        $this->assertEquals('Created from Factory', $data);
        $this->assertEquals('fooCombiService',$i1->getName());

        $i2 = $di->get('foo2CombiService');
        $this->assertEquals(__NAMESPACE__.'\CombiService', get_class($i2));
        $data = $i2->getData();
        $this->assertEquals('Created from Factory', $data);
        $this->assertEquals('foo2CombiService',$i2->getName());
    }

    public function testFactoryWithArgs()
    {
        $config = array (
            'components' => array(
                'TestComponent' => array(
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                    'factory_args' => array(
                        'a'=>'b',
                    ),
                ),
            ),
        );
        $di = new Container($config);
        $i0 = $di->get('TestComponent');
        $this->assertEquals(__NAMESPACE__.'\CombiService', get_class($i0));
        $this->assertEquals('Created from Factory', $i0->getData());
        $this->assertEquals('TestComponent', $i0->getName());
        $this->assertEquals(array('a'=>'b'), $i0->getArgs());
    }

    public function testGetInstanceByAlias()
    {
        $config = array (
            'aliases' => array(
                'alias0' => __NAMESPACE__.'\Param0',
                'instance1' => 'instance',
            ),
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $mgr = new Container($config);
        $this->assertTrue($mgr->has(__NAMESPACE__.'\Param0'));
        $this->assertTrue($mgr->has('alias0'));
        $this->assertEquals(__NAMESPACE__.'\Param0', get_class($mgr->get('alias0')));

        $this->assertFalse($mgr->has('instance1'));
        $this->assertFalse($mgr->has('instance'));
        $mgr->setInstance('instance','xyz');
        $this->assertTrue($mgr->has('instance1'));
        $this->assertTrue($mgr->has('instance'));
        $this->assertEquals('xyz', $mgr->get('instance1'));
    }

    public function testOverloadInstanceByAlias()
    {
        $mgr = new Container();
        $mgr->getComponentManager()->addAlias('test0','test2');
        $mgr->setInstance('test0','instance0');
        $mgr->setInstance('test2','instance2');
        $this->assertEquals('instance2',$mgr->get('test0'));
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage Undefined component.: test0
     */
    public function testGetInstanceByAliasNonDef()
    {
        $mgr = new Container();
        $mgr->getComponentManager()->addAlias('test','test0');
        $i0 = $mgr->get('test');
    }

    public function testHasNormal()
    {
        $im = new Container();
        $this->assertFalse($im->has('test0'));
        $im->setInstance('test0','dummy');
        $this->assertTrue($im->has('test0'));
        $this->assertFalse($im->has('test111'));

        $this->assertFalse($im->has('alias0'));
        $im->getComponentManager()->addAlias('alias0','test0');
        $this->assertTrue($im->has('alias0'));
    }

    public function testNullAlias()
    {
        $im = new Container();
        $im->getComponentManager()->addAlias('test',null);
        $im->getComponentManager()->addAlias('test',null);
        $im->getComponentManager()->addAlias('test',null);
        $this->assertNull($im->get('test'));
        $this->assertFalse($im->has('test'));

        $config = array(
            'aliases' => array(
                'test' => null,
            ),
        );
        $im = new Container($config);
        $this->assertNull($im->get('test'));
        $this->assertFalse($im->has('test'));
    }

    public function testHasFactory()
    {
        $config = array(
            'components' => array(
                'test0' => array(
                    'factory'=>__NAMESPACE__.'\CombiServiceFactory::factory'),
            ),
        );
        $im = new Container();
        $im->setConfig($config);

        $this->assertTrue($im->has('test0'));
        $this->assertFalse($im->has('test111'));

        $this->assertFalse($im->has('alias0'));
        $im->getComponentManager()->addAlias('alias0','test0');
        $this->assertTrue($im->has('alias0'));
    }

    public function testCombinationAliasAndFactory()
    {
        $config = array (
            'aliases' => array(
                __NAMESPACE__.'\CombiServiceInterface' => __NAMESPACE__.'\CombiService',
            ),
            'components' => array(
                __NAMESPACE__.'\CombiService' => array(
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                ),
                __NAMESPACE__.'\CombiDiClass1' => array(
                ),
                __NAMESPACE__.'\CombiDiClass2' => array(
                ),
            ),
        );

        $di = new Container($config);
        $i2 = $di->get(__NAMESPACE__.'\CombiDiClass2');
        $this->assertEquals(__NAMESPACE__.'\CombiDiClass2', get_class($i2));

        $i2s0 = $i2->getService0();
        $this->assertEquals(__NAMESPACE__.'\CombiService', get_class($i2s0));
        $i2c1 = $i2->getService1();
        $this->assertEquals(__NAMESPACE__.'\CombiDiClass1', get_class($i2c1));

        $i2c1s0 = $i2c1->getService();
        $this->assertEquals(__NAMESPACE__.'\CombiService', get_class($i2c1s0));

        $data = $i2c1s0->getData();
        $this->assertEquals('Created from Factory', $data);
    }

    public function testCombinationAliasAndFactoryWithoutRuntimeComplile()
    {
        $config = array (
            'runtime_complie' => false,
            'declarations' => array(
                array(
                    'class' => __NAMESPACE__.'\CombiDiClass1',
                    'constructor' => '__construct',
                    'injects' => array(
                        '__construct' => array(
                            'service' => array('ref'=>__NAMESPACE__.'\CombiServiceInterface'),
                        ),
                    ),
                ),
                array(
                    'class' => __NAMESPACE__.'\CombiDiClass2',
                    'constructor' => '__construct',
                    'injects' => array(
                        '__construct' => array(
                            'service0' => array('ref'=>__NAMESPACE__.'\CombiServiceInterface'),
                            'service1' => array('ref'=>__NAMESPACE__.'\CombiDiClass1'),
                        ),
                    ),
                ),
            ),
            'aliases' => array(
                __NAMESPACE__.'\CombiServiceInterface' => __NAMESPACE__.'\CombiService',
            ),
            'components' => array(
                __NAMESPACE__.'\CombiService' => array(
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                ),
                __NAMESPACE__.'\CombiDiClass1' => array(
                ),
                __NAMESPACE__.'\CombiDiClass2' => array(
                ),
            ),
        );

        $di = new Container($config);

        $i2 = $di->get(__NAMESPACE__.'\CombiDiClass2');
        $this->assertEquals(__NAMESPACE__.'\CombiDiClass2', get_class($i2));

        $i2s0 = $i2->getService0();
        $this->assertEquals(__NAMESPACE__.'\CombiService', get_class($i2s0));
        $i2c1 = $i2->getService1();
        $this->assertEquals(__NAMESPACE__.'\CombiDiClass1', get_class($i2c1));

        $i2c1s0 = $i2c1->getService();
        $this->assertEquals(__NAMESPACE__.'\CombiService', get_class($i2c1s0));

        $data = $i2c1s0->getData();
        $this->assertEquals('Created from Factory', $data);
    }

    public function testSetterInjection()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\SetterInjectionClass' => array(
                    'properties' => array(
                        'arg0' => array('ref'=>__NAMESPACE__.'\Param0'),
                        'arg1' => array('value'=>'xyz'),
                    ),
                ),
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $i = $di->get(__NAMESPACE__.'\SetterInjectionClass');
        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($i->getArg0()));
        $this->assertEquals('xyz',$i->getArg1());
    }

    public function testPropertyInjection()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\PropertyInjectionClass' => array(
                    'properties' => array(
                        'arg0' => array('ref'=>__NAMESPACE__.'\Param0'),
                        'arg1' => array('value'=>'xyz'),
                    ),
                ),
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $i = $di->get(__NAMESPACE__.'\PropertyInjectionClass');
        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($i->getArg0()));
        $this->assertEquals('xyz',$i->getArg1());
    }

    public function testDefaultValue()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\DefaultValueClass' => array(
                ),
            ),
        );
        $di = new Container($config);
        $i = $di->get(__NAMESPACE__.'\DefaultValueClass');
        $this->assertEquals(null,$i->getArg0());
        $this->assertEquals(DefaultValueClass::VALUE100,$i->getArg1());

        \Rindow\Stdlib\Cache\CacheFactory::clearCache();

        $config = array(
            'components' => array(
                __NAMESPACE__.'\DefaultValueClass' => array(
                    'constructor_args' => array(
                        'arg0' => array('ref'=>__NAMESPACE__.'\Param0'),
                        'arg1' => array('value'=>123),
                    ),
                ),
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $i = $di->get(__NAMESPACE__.'\DefaultValueClass');
        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($i->getArg0()));
        $this->assertEquals(123,$i->getArg1());
    }

    public function testCache()
    {
        $diConfig = array (
            'cache_path' => '/di/cache',
            'components' => array(
                __NAMESPACE__.'\Param1' => array(
                ),
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );

        $di = new Container($diConfig);
        $i1 = $di->get(__NAMESPACE__.'\\Param1');
        $this->assertEquals(__NAMESPACE__.'\\Param0', get_class($i1->getArg1()));
        unset($di);

        $di2 = new Container($diConfig);
        $i1 = $di2->get(__NAMESPACE__.'\\Param1');
        $this->assertEquals(__NAMESPACE__.'\\Param0', get_class($i1->getArg1()));
    }
    public function testFieldAnnotationInjection()
    {
        $diConfig = array (
            //'annotation_manager' => true,
            'components' => array(
                __NAMESPACE__.'\FieldAnnotationInjection' => array(
                ),
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($diConfig);
        $di->setAnnotationManager(new AnnotationManager());
        $model1 = $di->get(__NAMESPACE__.'\\FieldAnnotationInjection');
        $arg0 = $model1->getArg0();
        $this->assertEquals(__NAMESPACE__.'\\Param0',get_class($arg0));
    }
    public function testPropertyAccessInjection()
    {
        $diConfig = array (
            //'annotation_manager' => true,
            'components' => array(
                __NAMESPACE__.'\PropertyAccessInjection' => array(
                ),
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($diConfig);
        $di->setAnnotationManager(new AnnotationManager());
        $model1 = $di->get(__NAMESPACE__.'\\PropertyAccessInjection');
        $arg0 = $model1->arg0;
        $this->assertEquals(__NAMESPACE__.'\\Param0',get_class($arg0));
    }
    public function testMultiArgInjection()
    {
        $diConfig = array (
            //'annotation_manager' => true,
            'components' => array(
                __NAMESPACE__.'\MultiArgInjection' => array(
                ),
                __NAMESPACE__.'\Param0' => array(
                ),
                __NAMESPACE__.'\Param1' => array(
                ),
            ),
        );
        $di = new Container($diConfig);
        $di->setAnnotationManager(new AnnotationManager());
        $model1 = $di->get(__NAMESPACE__.'\\MultiArgInjection');
        $this->assertEquals(__NAMESPACE__.'\\Param0',get_class($model1->getArg0()));
        $this->assertEquals(__NAMESPACE__.'\\Param1',get_class($model1->getArg1()));
    }

    public function testAnnotationComponentNormal()
    {
        $diConfig = array (
            //'annotation_manager' => true,
            'component_paths' => array(
                self::$RINDOW_TEST_RESOURCES.'/AcmeTest/DiContainer/Component' => true,
            ),
        );
        $di = new Container($diConfig);
        $di->setAnnotationManager(new AnnotationManager());
        $di->scanComponents();
        $cm = $di->getComponentManager();
        $this->assertEquals('AcmeTest\DiContainer\Component\Model0',$cm->getScannedComponent('model0'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model1',$cm->getScannedComponent('model1'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model2',$cm->getScannedComponent('model2'));

        $model2 = $di->get('model2');
        $this->assertEquals('AcmeTest\DiContainer\Component\Model2',get_class($model2));
        $model1 = $model2->getModel1();
        $this->assertEquals('AcmeTest\DiContainer\Component\Model1',get_class($model1));
        $model0 = $model1->getModel0();
        $this->assertEquals('AcmeTest\DiContainer\Component\Model0',get_class($model0));
    }

    public function testAnnotationComponentAsDefinedComponent()
    {
        $diConfig = array (
            //'annotation_manager' => true,
            'component_paths' => array(
                self::$RINDOW_TEST_RESOURCES.'/AcmeTest/DiContainer/Component' => true,
            ),
        );
        $di = new Container($diConfig);
        $di->setAnnotationManager(new AnnotationManager());
        $di->scanComponents();
        $cm = $di->getComponentManager();
        $component = $cm->getComponent('model0');
        $this->assertEquals('AcmeTest\DiContainer\Component\Model0',$component->getClassName());
    }

    public function testParent()
    {
        $config = array(
            'aliases' => array(
                'parent' => __NAMESPACE__.'\Param0',
                'test'   => __NAMESPACE__.'\Param0',
            ),
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $parent = new Container($config);
        $config = array(
            'aliases' => array(
                'child'  => __NAMESPACE__.'\Param1',
                'test'   => __NAMESPACE__.'\Param1',
            ),
            'components' => array(
                __NAMESPACE__.'\Param1' => array(
                ),
            ),
        );
        $child = new Container($config);
        $child->setParentManager($parent);

        $this->assertFalse($parent->has('child'));
        $this->assertTrue($parent->has('parent'));
        $this->assertTrue($parent->has('test'));

        $this->assertTrue($child->has('child'));
        $this->assertTrue($child->has('parent'));
        $this->assertTrue($child->has('test'));

        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($parent->get('parent')));
        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($parent->get('test')));

        $this->assertEquals(__NAMESPACE__.'\Param1',get_class($child->get('child')));
        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($child->get('parent')));
        $this->assertEquals(__NAMESPACE__.'\Param1',get_class($child->get('test')));
    }

    public function testSingleton()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\CombiService' => array(
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                ),
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $i0 = $di->get(__NAMESPACE__.'\Param0');
        $i1 = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(spl_object_hash($i0),spl_object_hash($i1));

        $i0 = $di->get(__NAMESPACE__.'\CombiService');
        $i1 = $di->get(__NAMESPACE__.'\CombiService');
        $this->assertEquals(spl_object_hash($i0),spl_object_hash($i1));
    }

    public function testNamedComponentOnConfig()
    {
        $config = array (
            'components' => array(
                'CombiService' => array(
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                ),
                'CombiDiClass1' => array(
                    'class' => __NAMESPACE__.'\CombiDiClass1',
                    'constructor_args' => array(
                        'service' => array('ref'=>'CombiService'),
                    ),
                ),
                'CombiDiClass2' => array(
                    'class' => __NAMESPACE__.'\CombiDiClass2',
                    'constructor_args' => array(
                        'service0' => array('ref'=>'CombiService'),
                        'service1' => array('ref'=>'CombiDiClass1'),
                    ),
                ),
                'Param2WithValue1' => array(
                    'class' => __NAMESPACE__.'\Param2',
                    'constructor_args' => array(
                        'arg1' => array('value'=>'value1'),
                    ),
                ),
                'Param2WithValue2' => array(
                    'class' => __NAMESPACE__.'\Param2',
                    'constructor_args' => array(
                        'arg1' => array('value'=>'value2'),
                    ),
                ),
            ),
        );
        $di = new Container($config);
        $i2 = $di->get('CombiDiClass2');
        $this->assertEquals(__NAMESPACE__.'\CombiDiClass2',get_class($i2));
        $this->assertEquals(__NAMESPACE__.'\CombiService',get_class($i2->getService0()));
        $this->assertEquals(__NAMESPACE__.'\CombiDiClass1',get_class($i2->getService1()));
        $this->assertEquals(__NAMESPACE__.'\CombiService',get_class($i2->getService1()->getService()));

        $v1 = $di->get('Param2WithValue1');
        $v2 = $di->get('Param2WithValue2');
        $this->assertEquals('value1',$v1->getArg1());
        $this->assertEquals('value2',$v2->getArg1());
    }

    public function testScope()
    {
        $config = array (
            'components' => array(
                'Prototype' => array(
                    'class' => __NAMESPACE__.'\Param0',
                    'scope' => 'prototype',
                ),
                'Singleton' => array(
                    'class' => __NAMESPACE__.'\Param0',
                    'scope' => 'singleton',
                ),
                'PrototypeFactory' => array(
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                    'scope' => 'prototype',
                ),
                'SingletonFactory' => array(
                    'factory' => __NAMESPACE__.'\CombiServiceFactory::factory',
                    'scope' => 'singleton',
                ),
            ),
        );
        $di = new Container($config);
        $i0 = $di->get('Prototype');
        $i1 = $di->get('Prototype');
        $this->assertNotEquals(spl_object_hash($i0),spl_object_hash($i1));

        $i0 = $di->get('Singleton');
        $i1 = $di->get('Singleton');
        $this->assertEquals(spl_object_hash($i0),spl_object_hash($i1));

        $i0 = $di->get('PrototypeFactory');
        $i1 = $di->get('PrototypeFactory');
        $this->assertNotEquals(spl_object_hash($i0),spl_object_hash($i1));

        $i0 = $di->get('SingletonFactory');
        $i1 = $di->get('SingletonFactory');
        $this->assertEquals(spl_object_hash($i0),spl_object_hash($i1));
    }

    public function testScopeAnnotation()
    {
        $config = array (
            //'annotation_manager' => true,
            'components' => array(
                __NAMESPACE__.'\PrototypeScope' => array(
                ),
                __NAMESPACE__.'\SingletonScope' => array(
                ),
            ),
        );
        $di = new Container($config);
        $di->setAnnotationManager(new AnnotationManager());
        $i0 = $di->get(__NAMESPACE__.'\PrototypeScope');
        $i1 = $di->get(__NAMESPACE__.'\PrototypeScope');
        $this->assertNotEquals(spl_object_hash($i0),spl_object_hash($i1));

        $i0 = $di->get(__NAMESPACE__.'\SingletonScope');
        $i1 = $di->get(__NAMESPACE__.'\SingletonScope');
        $this->assertEquals(spl_object_hash($i0),spl_object_hash($i1));
    }

    public function testPostConstructClass()
    {
        $config = array (
            //'annotation_manager' => false,
            'components' => array(
                __NAMESPACE__.'\PostConstructClass' => array(
                    'properties' => array(
                        'arg0' => array('value' => 'xyz'),
                    ),
                    'init_method' => 'init',
                ),
            ),
        );
        $di = new Container($config);
        $i0 = $di->get(__NAMESPACE__.'\PostConstructClass');
        $this->assertEquals('xyz',$i0->getArg0());
        $this->assertEquals(true,$i0->is_initialized());
    }

    public function testPostConstructClassByAnnotation()
    {
        $config = array (
            //'annotation_manager' => false,
            'components' => array(
                __NAMESPACE__.'\PostConstructClass' => array(
                ),
            ),
        );
        $di = new Container($config);
        $i0 = $di->get(__NAMESPACE__.'\PostConstructClass');
        $this->assertEquals(null,$i0->getArg0());
        $this->assertEquals(null,$i0->is_initialized());

        \Rindow\Stdlib\Cache\CacheFactory::clearCache();

        $config = array(
            //'annotation_manager' => true,
            'components' => array(
                __NAMESPACE__.'\PostConstructClass' => array(
                ),
            ),
        );
        $di = new Container($config);
        $di->setAnnotationManager(new AnnotationManager());
        $i0 = $di->get(__NAMESPACE__.'\PostConstructClass');
        $this->assertEquals(123,$i0->getArg0());
        $this->assertEquals(true,$i0->is_initialized());
    }
    public function testConfigInjection()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\Param2' => array(
                    'constructor_args' => array(
                        'arg1' => array('ref'=>'translator::language'),
                    ),
                ),
                'translator::language' => array(
                    'factory' => 'Rindow\Container\ConfigurationFactory::factory',
                ),
            ),
        );
        $di = new Container($config);
        $config = array(
            'translator' => array(
                'language' => 'ja_JP',
            ),
        );
        $di->setInstance('config',$config);
        $i = $di->get(__NAMESPACE__.'\Param2');
        $this->assertEquals('ja_JP',$i->getArg1());
    }
    public function testConfigInjection2()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\Param2' => array(
                    'constructor_args' => array(
                        'arg1' => array('ref'=>'translator_language'),
                    ),
                ),
                'translator_language' => array(
                    'factory' => 'Rindow\Container\ConfigurationFactory::factory',
                    'factory_args' => array('config'=>'translator::language'),
                ),
            ),
        );
        $di = new Container($config);
        $config = array(
            'translator' => array(
                'language' => 'ja_JP',
            ),
        );
        $di->setInstance('config',$config);
        $i = $di->get(__NAMESPACE__.'\Param2');
        $this->assertEquals('ja_JP',$i->getArg1());
    }
    public function testConfigInjection3()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\Param2' => array(
                    'constructor_args' => array(
                        'arg1' => array('ref'=>'translator_language'),
                    ),
                ),
                'translator_language' => array(
                    'factory' => 'Rindow\Container\ConfigurationFactory::factory',
                    'factory_args' => array('config'=>'translator'),
                ),
            ),
        );
        $di = new Container($config);
        $config = array(
            'translator' => array(
                'language' => 'ja_JP',
            ),
        );
        $di->setInstance('config',$config);
        $i = $di->get(__NAMESPACE__.'\Param2');
        $this->assertEquals(array('language' => 'ja_JP'),$i->getArg1());
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage a recursion dependency is detected in
     */
    public function testRecursionComponent()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\TestRecursionA' => array(
                    'properties' => array(
                        'obj' => array('ref' => __NAMESPACE__.'\TestRecursionB'),
                    ),
                ),
                __NAMESPACE__.'\TestRecursionB' => array(
                    'properties' => array(
                        'obj' => array('ref' => __NAMESPACE__.'\TestRecursionA'),
                    ),
                ),
            ),
        );
        $di = new Container($config);
        $obj = $di->get(__NAMESPACE__.'\TestRecursionA');
    }

    public function testMultiDepthAliases()
    {
        $config = array(
            'aliases' => array(
                'recursion1' => 'recursion2',
                'recursion2' => __NAMESPACE__.'\Param0',
            ),
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $obj = $di->get('recursion1');
        $this->assertInstanceof(__NAMESPACE__.'\Param0',$obj);
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage a recursion alias is detected in
     */
    public function testRecursionAliases()
    {
        $config = array(
            'aliases' => array(
                'recursion1' => 'recursion1'
            ),
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $obj = $di->get('recursion1');
    }

    public function testMultiComponent()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\TestMultiComponentA' => array(
                    'class' => __NAMESPACE__.'\SetterInjectionClass',
                    'properties' => array(
                        'arg0' => array('ref'=>__NAMESPACE__.'\Param0'),
                        'arg1' => array('value'=>'xyz'),
                    ),
                ),
                __NAMESPACE__.'\TestMultiComponentB' => array(
                    'class' => __NAMESPACE__.'\SetterInjectionClass',
                ),
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $objA = $di->get(__NAMESPACE__.'\TestMultiComponentA');
        $this->assertInstanceof(__NAMESPACE__.'\Param0',$objA->getArg0());
        $this->assertEquals('xyz',$objA->getArg1());
        $objB = $di->get(__NAMESPACE__.'\TestMultiComponentB');
        $this->assertNull($objB->getArg0());
        $this->assertNull($objB->getArg1());
    }

    public function testInjectNamedConfig()
    {
        $config = array(
            'test' => array('named'=>array('config'=>array(
                'value' => 'fooValue',
                'setter' => 'fooSetter',
            ))),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\\InjectNamedConfig' => array(
                    ),
                ),
            ),
        );
        $di = new Container($config['container']);
        $di->setAnnotationManager(new AnnotationManager());
        $di->setInstance('config',$config);
        $obj = $di->get(__NAMESPACE__.'\\InjectNamedConfig');
        $this->assertEquals('fooValue',$obj->getTestValue());
        $this->assertEquals('fooSetter',$obj->getTestSetter());
    }

    public function testInjectRefAtWithoutAnnotationManager()
    {
        $config = array(
            'test' => array('named'=>array('config'=>array(
                'value' => __NAMESPACE__.'\\InjectNamedInForValue',
                'setter' => __NAMESPACE__.'\\InjectNamedInForSetter',
            ))),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\\InjectNamedIn' => array(
                        'properties' => array(
                            'testValue' => array('ref@'=>'test::named::config::value'),
                        ),
                        'injects' => array(
                            'setTestSetter' => array(
                                'testSetter' => array('ref@'=>'test::named::config::setter'),
                            ),
                        ),
                    ),
                    __NAMESPACE__.'\\InjectNamedInForValue' => array(
                        'class' => __NAMESPACE__.'\\InjectNamedInConfig',
                        'properties' => array(
                            'value' => array('value'=>'InjectNamedInForValue'),
                        ),
                    ),
                    __NAMESPACE__.'\\InjectNamedInForSetter' => array(
                        'class' => __NAMESPACE__.'\\InjectNamedInConfig',
                        'properties' => array(
                            'value' => array('value'=>'InjectNamedInForSetter'),
                        ),
                    ),
                ),
            ),
        );
        $di = new Container($config['container']);
        $di->setInstance('config',$config);
        $obj = $di->get(__NAMESPACE__.'\\InjectNamedIn');
        $this->assertEquals('InjectNamedInForValue',$obj->getTestValue()->getValue());
        $this->assertEquals('InjectNamedInForSetter',$obj->getTestSetter()->getValue());
    }

    public function testInjectNamedIn()
    {
        $config = array(
            'test' => array('named'=>array('config'=>array(
                'value' => __NAMESPACE__.'\\InjectNamedInForValue',
                'setter' => __NAMESPACE__.'\\InjectNamedInForSetter',
            ))),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\\InjectNamedIn' => array(
                    ),
                    __NAMESPACE__.'\\InjectNamedInForValue' => array(
                        'class' => __NAMESPACE__.'\\InjectNamedInConfig',
                        'properties' => array(
                            'value' => array('value'=>'InjectNamedInForValue'),
                        ),
                    ),
                    __NAMESPACE__.'\\InjectNamedInForSetter' => array(
                        'class' => __NAMESPACE__.'\\InjectNamedInConfig',
                        'properties' => array(
                            'value' => array('value'=>'InjectNamedInForSetter'),
                        ),
                    ),
                ),
            ),
        );
        $di = new Container($config['container']);
        $di->setAnnotationManager(new AnnotationManager());
        $di->setInstance('config',$config);
        $obj = $di->get(__NAMESPACE__.'\\InjectNamedIn');
        $this->assertEquals('InjectNamedInForValue',$obj->getTestValue()->getValue());
        $this->assertEquals('InjectNamedInForSetter',$obj->getTestSetter()->getValue());
    }
}
