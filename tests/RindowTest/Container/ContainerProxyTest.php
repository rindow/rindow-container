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
    public function newProxy(Container $container,ComponentDefinition $component)
    {
        # code...
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

    public function createTestMock($className,$methods = array(), array $arguments = array())
    {
        $args = func_get_args();
        if(count($args)==0 || count($args)>3)
            throw new \Exception('illegal mock style');
        $builder = $this->getMockBuilder($className);
        $builder->setMethods($methods);
        $builder->setConstructorArgs($arguments);
        return $builder->getMock();
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
        $proxyManager = $this->createTestMock(__NAMESPACE__.'\TestProxyManager');
        $proxyManager->expects($this->never())
                ->method('newProxy');
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($i));
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
        $proxyManager = $this->createTestMock(__NAMESPACE__.'\TestProxyManager');
        $proxyManager->expects($this->once())
                ->method('newProxy')
                ->with($this->equalTo($di),
                    $this->callback(function($component) {
                        if($component->getName()==__NAMESPACE__.'\Param0')
                            return true;
                        return false;
                    }))
                ->will($this->returnValue(new Param0Proxy()));
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
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
        $proxyManager = $this->createTestMock(__NAMESPACE__.'\TestProxyManager');
        $proxyManager->expects($this->once())
                ->method('newProxy')
                ->with($this->equalTo($di),
                    $this->callback(function($component) {
                        if($component->getName()==__NAMESPACE__.'\Param0')
                            return true;
                        return false;
                    }))
                ->will($this->returnValue(new Param0Proxy()));
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
    }

    public function testAllAutoProxy()
    {
        $config = array(
            'implicit_component' => true,
            'auto_proxy' => 'all',
        );
        $di = new Container($config);
        $proxyManager = $this->createTestMock(__NAMESPACE__.'\TestProxyManager');
        $proxyManager->expects($this->once())
                ->method('newProxy')
                ->with($this->equalTo($di),
                    $this->callback(function($component) {
                        if($component->getName()==__NAMESPACE__.'\Param0')
                            return true;
                        return false;
                    }))
                ->will($this->returnValue(new Param0Proxy()));
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
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
        $proxyManager = $this->createTestMock(__NAMESPACE__.'\TestProxyManager');
        $proxyManager->expects($this->once())
                ->method('newProxy')
                ->with($this->equalTo($di),
                    $this->callback(function($component) {
                        if($component->getName()==__NAMESPACE__.'\Param0' &&
                            $component->getProxyMode()=='interface')
                            return true;
                        return false;
                    }),
                    $this->equalTo(array('mode'=>'interface')))
                ->will($this->returnValue(new Param0Proxy()));
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
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
        $proxyManager = $this->createTestMock(__NAMESPACE__.'\TestProxyManager');
        $proxyManager->expects($this->never())
                ->method('newProxy');
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0');
        $this->assertEquals(__NAMESPACE__.'\Param0',get_class($i));
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
        $proxyManager = $this->createTestMock(__NAMESPACE__.'\TestProxyManager');
        $proxyManager->expects($this->once())
                ->method('newProxy')
                ->with($this->equalTo($di),
                    $this->callback(function($component) {
                        if($component->getName()==__NAMESPACE__.'\Param0Ann' &&
                            $component->getProxyMode()==null)
                            return true;
                        return false;
                    }),
                    $this->equalTo(array('mode'=>'interface')))
                ->will($this->returnValue(new Param0Proxy()));
        $di->setProxyManager($proxyManager);
        $i = $di->get(__NAMESPACE__.'\Param0Ann');
        $this->assertEquals(__NAMESPACE__.'\Param0Proxy',get_class($i));
    }
}