<?php
namespace RindowTest\Container\ComponentManagerTest;

use PHPUnit\Framework\TestCase;
use Rindow\Annotation\AnnotationManager;

// Test Target Classes
use Rindow\Container\ComponentDefinitionManager;
use Rindow\Container\ComponentScanner;

class Test extends TestCase
{
    static $RINDOW_TEST_RESOURCES;
    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/../../resources';
    }

    public function setUp()
    {
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
    }

    public function testCache()
    {
        $this->markTestIncomplete(
            'Should test on cache'
        );
    }

    public function testConfig()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\Test0' => array(
                    'factory' => __NAMESPACE__.'\Test0::factory',
                ),
                __NAMESPACE__.'\Test1' => array(
                ),
                'test1' => array(
                    'class' => __NAMESPACE__.'\Test1',
                    'constructor_args' => array(
                        'arg0' => array('ref'  =>'RindowTest\DiComponentTest\Test0'),
                        'arg1' => array('value'=>'value1'),
                    ),
                ),
            ),
        );
        $mgr = new ComponentDefinitionManager();
        $mgr->setConfig($config);

        $component = $mgr->getComponent(__NAMESPACE__.'\Test0');
        $this->assertEquals(array(),$component->getInjects());
        $this->assertEquals(false,$component->getInject('__construct'));
        $this->assertEquals(true,$component->hasFactory());
        $this->assertEquals(__NAMESPACE__.'\Test0::factory',$component->getFactory());
        $this->assertEquals(__NAMESPACE__.'\Test0',$component->getName());
        $this->assertEquals(null,$component->getClassName());
        
        $component = $mgr->getComponent(__NAMESPACE__.'\Test1');
        $this->assertEquals(array(),$component->getInjects());
        $this->assertEquals(false,$component->getInject('__construct'));
        $this->assertEquals(false,$component->hasFactory());
        $this->assertEquals(null,$component->getFactory());
        $this->assertEquals(__NAMESPACE__.'\Test1',$component->getName());
        $this->assertEquals(__NAMESPACE__.'\Test1',$component->getClassName());

        $injects = array(
            '__construct' => array(
                'arg0' => array('ref'  =>'RindowTest\DiComponentTest\Test0'),
                'arg1' => array('value'=>'value1'),
            ),
        );
        $construct = array(
            'arg0' => array('ref'  =>'RindowTest\DiComponentTest\Test0'),
            'arg1' => array('value'=>'value1'),
        );
        $component = $mgr->getComponent('test1');
        $this->assertEquals(__NAMESPACE__.'\Test1',$component->getClassName());
        $this->assertEquals('test1',$component->getName());
        $this->assertEquals($injects,$component->getInjects());
        $this->assertEquals($construct,$component->getInject('__construct'));
        $this->assertEquals(false,$component->getInject('none'));
        $this->assertEquals(false,$component->hasFactory());
        $this->assertEquals(null,$component->getFactory());

        $this->assertFalse($mgr->getComponent('TestComponent'));
        $component = $mgr->getComponent('TestComponentForce',true);
        $this->assertEquals('Rindow\Container\ComponentDefinition',get_class($component));
        $this->assertEquals('TestComponentForce',$component->getClassName());
        $this->assertEquals('TestComponentForce',$component->getName());
        $this->assertEquals(array(),$component->getInjects());
        $this->assertEquals(null,$component->getFactory());
    }

    public function testGetNew()
    {
        $mgr = new ComponentDefinitionManager();
        $component = $mgr->getComponent('test',true);
        $component->addPropertyWithValue('var1','value1');

        $component2 = $mgr->getComponent('test');
        $injects = array(
            'setVar1' => array(
                'var1' => array('value'=>'value1'),
            ),
        );
        $this->assertEquals($injects,$component2->getInjects());
    }

    public function testHasComponent()
    {
        $mgr = new ComponentDefinitionManager();
        $this->assertFalse($mgr->hasComponent('test'));
        $component = $mgr->getComponent('test',true);
        $this->assertTrue($mgr->hasComponent('test'));
    }

    public function testAddAlias()
    {
        $mgr = new ComponentDefinitionManager();
        $alias = 'Alias';
        $className = 'RindowTest\ServiceManagerTest\CreateInstance0';
        $mgr->addAlias($alias,$className);
        $this->assertEquals($className,$mgr->resolveAlias($alias));
    }

    public function testgetAliasNonDef()
    {
        $mgr = new ComponentDefinitionManager();
        $alias = 'NonDef';
        $this->assertEquals($alias, $mgr->resolveAlias($alias));
    }

    public function testNamedComponentWithCache()
    {
        $annotaionManager = new AnnotationManager();
        $mgr = new ComponentDefinitionManager();
        $mgr->setAnnotationManager($annotaionManager);
        $mgr->setEnableCache(true);
        $mgr->setConfig(array());
        $componentScanner = new ComponentScanner();
        $componentScanner->setAnnotationManager($annotaionManager);
        $mgr->attachScanner($componentScanner);
        $componentScanner->scan(array(self::$RINDOW_TEST_RESOURCES.'/AcmeTest/DiContainer/Component'=>true));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model0',$mgr->getScannedComponent('model0'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model1',$mgr->getScannedComponent('model1'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model2',$mgr->getScannedComponent('model2'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model3',$mgr->getScannedComponent('AcmeTest\DiContainer\Component\Model3'));
        $component = $mgr->getComponent('model2');
        $this->assertEquals('model2',$component->getName());
        $this->assertEquals('AcmeTest\DiContainer\Component\Model2',$component->getClassName());
        $this->assertEquals(array('setModel1'=>array('model1'=>array('ref'=>'model1'))),$component->getInjects());

        $this->assertEquals(null,$mgr->getScannedComponent('AcmeTest\DiContainer\Component\ModelX'));
        $this->assertEquals(false,$mgr->getComponent('AcmeTest\DiContainer\Component\ModelX'));

        // ================== cached =============
        $annotaionManager = new AnnotationManager();
        $mgr = new ComponentDefinitionManager();
        $mgr->setAnnotationManager($annotaionManager);
        $mgr->setEnableCache(true);
        $mgr->setConfig(array());
        $componentScanner = new ComponentScanner();
        $componentScanner->setAnnotationManager($annotaionManager);
        $mgr->attachScanner($componentScanner);
        $componentScanner->scan(array(self::$RINDOW_TEST_RESOURCES.'/AcmeTest/DiContainer/Component'=>true));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model0',$mgr->getScannedComponent('model0'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model1',$mgr->getScannedComponent('model1'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model2',$mgr->getScannedComponent('model2'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model3',$mgr->getScannedComponent('AcmeTest\DiContainer\Component\Model3'));
        $component = $mgr->getComponent('model2');
        $this->assertEquals('model2',$component->getName());
        $this->assertEquals('AcmeTest\DiContainer\Component\Model2',$component->getClassName());
        $this->assertEquals(array('setModel1'=>array('model1'=>array('ref'=>'model1'))),$component->getInjects());

        $this->assertEquals(null,$mgr->getScannedComponent('AcmeTest\DiContainer\Component\ModelX'));
        $this->assertEquals(false,$mgr->getComponent('AcmeTest\DiContainer\Component\ModelX'));
    }

    public function testNamedComponentWithOutCache()
    {
        $annotaionManager = new AnnotationManager();
        $mgr = new ComponentDefinitionManager();
        $mgr->setAnnotationManager($annotaionManager);
        $mgr->setEnableCache(false);
        $mgr->setConfig(array());
        $componentScanner = new ComponentScanner();
        $componentScanner->setAnnotationManager($annotaionManager);
        $mgr->attachScanner($componentScanner);
        $componentScanner->scan(array(self::$RINDOW_TEST_RESOURCES.'/AcmeTest/DiContainer/Component'=>true));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model0',$mgr->getScannedComponent('model0'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model1',$mgr->getScannedComponent('model1'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model2',$mgr->getScannedComponent('model2'));
        $this->assertEquals('AcmeTest\DiContainer\Component\Model3',$mgr->getScannedComponent('AcmeTest\DiContainer\Component\Model3'));
        $component = $mgr->getComponent('model2');
        $this->assertEquals('model2',$component->getName());
        $this->assertEquals('AcmeTest\DiContainer\Component\Model2',$component->getClassName());
        $this->assertEquals(array('setModel1'=>array('model1'=>array('ref'=>'model1'))),$component->getInjects());

        $this->assertEquals(null,$mgr->getScannedComponent('AcmeTest\DiContainer\Component\ModelX'));
        $this->assertEquals(false,$mgr->getComponent('AcmeTest\DiContainer\Component\ModelX'));
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage directory not found:
     */
    public function testInvalidScanDirectory()
    {
        $mgr = new ComponentDefinitionManager();
        $mgr->setEnableCache(false);
        $mgr->setConfig(array());
        $componentScanner = new ComponentScanner();
        $componentScanner->setAnnotationManager(new AnnotationManager());
        $mgr->attachScanner($componentScanner);
        $componentScanner->scan(array(self::$RINDOW_TEST_RESOURCES.'/AcmeTest/DiContainer/Non'=>true));
    }

    public function testComponentInheritanceOnConfig()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\Test0' => array(
                    'parent' => __NAMESPACE__.'\ParentComponent0',
                ),
                __NAMESPACE__.'\Test1' => array(
                    'parent' => __NAMESPACE__.'\ParentComponent0',
                    'constructor_args' => array(
                        'arg1' => array('value'=>'replaced'),
                    ),
                ),
                __NAMESPACE__.'\ParentComponent0' => array(
                    'parent' => __NAMESPACE__.'\ParentComponent1',
                    'constructor_args' => array(
                        'arg0' => array('ref'  =>'RindowTest'),
                        'arg1' => array('value'=>'original'),
                    ),
                ),
                __NAMESPACE__.'\ParentComponent1' => array(
                    'class' => __NAMESPACE__.'\TestClass',
                ),
            ),
        );
        $mgr = new ComponentDefinitionManager();
        $mgr->setConfig($config);

        $component = $mgr->getComponent(__NAMESPACE__.'\Test0');
        $this->assertEquals(__NAMESPACE__.'\Test0',$component->getName());
        $this->assertEquals(__NAMESPACE__.'\TestClass',$component->getClassName());
        $this->assertEquals(
            array(
                '__construct' => array(
                    'arg0' => array('ref'  =>'RindowTest'),
                    'arg1' => array('value'=>'original'),
                )
            ),
            $component->getInjects());

        $component = $mgr->getComponent(__NAMESPACE__.'\Test1');
        $this->assertEquals(__NAMESPACE__.'\Test1',$component->getName());
        $this->assertEquals(__NAMESPACE__.'\TestClass',$component->getClassName());
        $this->assertEquals(
            array(
                '__construct' => array(
                    'arg0' => array('ref'  =>'RindowTest'),
                    'arg1' => array('value'=>'replaced'),
                )
            ),
            $component->getInjects());
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage No parent component definition found: Parent in Test0
     */
    public function testParentComponentNotFound()
    {
        $config = array(
            'components' => array(
                'Test0' => array(
                    'parent' => 'Parent',
                ),
            ),
        );
        $mgr = new ComponentDefinitionManager();
        $mgr->setConfig($config);
        $component = $mgr->getComponent('Test0');
    }
}
