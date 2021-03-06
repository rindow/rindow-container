<?php
namespace RindowTest\Container\ContainerProxyTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\ProxyManager;
use Rindow\Container\ComponentDefinition;
use Rindow\Container\Container;
use Rindow\Annotation\AnnotationManager;

use Rindow\Container\Annotation\Proxy;

interface Param0Interface
{
}

class Param0 implements Param0Interface
{
}
class Param0Proxy extends Param0
{
}

/**
* @Proxy("interface")
*/
class Param0Ann implements Param0Interface
{
}

class TestProxyManager implements ProxyManager
{
    public $awakened = 0;
    public $container;
    public $component;
    public $proxyOptions;
    public $returnValue;
    public function newProxy(Container $container,ComponentDefinition $component,$proxyOptions=null)
    {
        $this->awakened++;
        $this->container = $container;
        $this->component = $component;
        $this->proxyOptions = $proxyOptions;
        return $this->returnValue;
    }
}

class Test  extends TestCase
{
    public static function setUpBeforeClass()
    {
    }

    public static function tearDownAfterClass()
    {
    }

    public function setUp()
    {
    }

    public function testGetProxyMode()
    {
        $config = array(
            //'annotation_manager' => true,
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                    'proxy'=>'interface',
                ),
            ),
        );
        $di = new Container($config);
        $di->setAnnotationManager(new AnnotationManager());
        $cm = $di->getComponentManager();
        $component = $cm->getComponent(__NAMESPACE__.'\Param0');
        $this->assertEquals('interface',$component->getProxyMode());

        $dm = $di->getDeclarationManager();
        $declaration = $dm->getDeclaration(__NAMESPACE__.'\Param0Ann');
        $this->assertEquals('interface',$declaration->getProxyMode());
    }

    public function testNoAutoProxy()
    {
        $config = array(
            'implicit_component' => true,
        );
        $di = new Container($config);
        $proxyManager = new TestProxyManager();
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($i));
        $this->assertEquals(0,$proxyManager->awakened);
    }

    public function testComponentAutoProxyDefault()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $proxyManager = new TestProxyManager();
        $proxyManager->returnValue = new Param0Proxy();
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
        $this->assertEquals(1,$proxyManager->awakened);
        $this->assertEquals(spl_object_hash($proxyManager->container),spl_object_hash($di));
        $this->assertEquals($proxyManager->component->getName(),__NAMESPACE__.'\Param0');
    }

    public function testComponentAutoProxy()
    {
        $config = array(
            'auto_proxy' => 'component',
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $proxyManager = new TestProxyManager();
        $proxyManager->returnValue = new Param0Proxy();
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
        $this->assertEquals(1,$proxyManager->awakened);
        $this->assertEquals(spl_object_hash($proxyManager->container),spl_object_hash($di));
        $this->assertEquals($proxyManager->component->getName(),__NAMESPACE__.'\Param0');
    }

    public function testAllAutoProxy()
    {
        $config = array(
            'implicit_component' => true,
            'auto_proxy' => 'all',
        );
        $di = new Container($config);
        $proxyManager = new TestProxyManager();
        $proxyManager->returnValue = new Param0Proxy();
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
        $this->assertEquals(1,$proxyManager->awakened);
        $this->assertEquals(spl_object_hash($proxyManager->container),spl_object_hash($di));
        $this->assertEquals($proxyManager->component->getName(),__NAMESPACE__.'\Param0');
    }

    public function testExplicitAutoProxy()
    {
        $config = array(
            'auto_proxy' => 'explicit',
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                    'proxy'=>'interface',
                ),
            ),
        );
        $di = new Container($config);
        $proxyManager = new TestProxyManager();
        $proxyManager->returnValue = new Param0Proxy();
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
        $this->assertEquals(1,$proxyManager->awakened);
        $this->assertEquals(spl_object_hash($proxyManager->container),spl_object_hash($di));
        $this->assertEquals($proxyManager->component->getName(),__NAMESPACE__.'\Param0');
        $this->assertEquals($proxyManager->component->getProxyMode(),'interface');
        $this->assertEquals($proxyManager->proxyOptions,array('mode'=>'interface'));
    }

    public function testExplicitAutoProxyNone()
    {
        $config = array(
            'auto_proxy' => 'explicit',
            'components' => array(
                __NAMESPACE__.'\Param0' => array(
                ),
            ),
        );
        $di = new Container($config);
        $proxyManager = new TestProxyManager();
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($i));
        $this->assertEquals(0,$proxyManager->awakened);
    }

    public function testExplicitAutoProxyAnnotation()
    {
        $config = array(
            //'annotation_manager' => true,
            'auto_proxy' => 'explicit',
            'components' => array(
                __NAMESPACE__.'\Param0Ann' => array(
                ),
            ),
        );
        $di = new Container($config);
        $di->setAnnotationManager(new AnnotationManager());
        $proxyManager = new TestProxyManager();
        $proxyManager->returnValue = new Param0Proxy();
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0Ann');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
        $this->assertEquals(1,$proxyManager->awakened);
        $this->assertEquals(spl_object_hash($proxyManager->container),spl_object_hash($di));
        $this->assertEquals($proxyManager->component->getName(),__NAMESPACE__.'\Param0Ann');
        $this->assertEquals($proxyManager->component->getProxyMode(),null);
        $this->assertEquals($proxyManager->proxyOptions,array('mode'=>'interface'));
    }
}
