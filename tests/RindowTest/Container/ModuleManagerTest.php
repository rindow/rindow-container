<?php
namespace RindowTest\Container\ModuleManagerTest;

use PHPUnit\Framework\TestCase;
use Rindow\Loader\AutoLoader;
use stdClass;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;

// Test Target Classes
use Rindow\Container\ModuleManager;

class TestFilter
{
    public static function doSomething($config)
    {
        $config['test_additional'] = 'foo';
        return $config;
    }
}

class TestCheckDependencyModule
{
    public static $checked;
    public function getConfig()
    {
        return array(
        );
    }

    public function checkDependency($config)
    {
        self::$checked = true;
    }
}

class Test extends TestCase
{
    public static function setUpBeforeClass()
    {
        $cacheFactory = new \Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory();
        $cacheFactory->create('',$forceFileCache=true)->clear();
    }
    public static function tearDownAfterClass()
    {
        $cacheFactory = new \Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory();
        $cacheFactory->create('')->clear();
    }

    public function setUp()
    {
        $cacheFactory = new \Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory();
        $cacheFactory->create('')->clear();
    }

    public function getCacheConfig()
    {
        $config = array(
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
        );
        return $config;
    }

    public function testArrayReplaceRecursive()
    {
        $this->assertEquals(
            array('a'=>2,'b'=>3),
            array_replace_recursive(
                array('a'=>1),
                array('a'=>2,'b'=>3))
        );
        $this->assertNotEquals(
            array('a','b'),
            array_replace_recursive(
                array('a'),
                array('b'))
        );
        $this->assertEquals(
            array('a'=>false,'b'=>'b'),
            array_replace_recursive(
                array('a'=>array('a-1'=>1,'a-2'=>2),'b'=>'b'),
                array('a'=>false))
        );
    }

    public function testConfigNormal()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                ),
                'enableCache' => false,
            ),
            'global_config' => array(
                'global'    => 'This is global',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $config = $moduleManager->getConfig();
        $this->assertEquals('This is global', $config['global_config']['global']);
        $this->assertEquals('module1', $config['module_setting']['each_setting']['AcmeTest\Module1']);
        $this->assertEquals(array('AcmeTest\Module1'=>'testpath1'), $config['module_setting']['share_setting']['paths']);
    }

    public function testConfigNormalDouble()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
                'enableCache' => false,
            ),
            'global_config' => array(
                'global'    => 'This is global',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $config = $moduleManager->getConfig();
        $this->assertEquals('This is global', $config['global_config']['global']);
        $this->assertEquals('module1', $config['module_setting']['each_setting']['AcmeTest\Module1']);
        $this->assertEquals('module2', $config['module_setting']['each_setting']['AcmeTest\Module2']);
        $this->assertEquals(array('AcmeTest\Module1'=>'testpath1','AcmeTest\Module2'=>'testpath2'), $config['module_setting']['share_setting']['paths']);
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage A class is not found:AcmeTest\None\Module
     */
    public function testConfigNotfound()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\None\Module' => true,
                ),
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $moduleManager->getConfig();
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage Modules are not defined in module manager configuration.
     */
    public function testConfigNone()
    {
        $config = array(
            'module_manager' => array(
                'modules' => null,
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $moduleManager->getConfig();
    }

    /**
     * @expectedException        Rindow\Container\Exception\InvalidArgumentException
     * @expectedExceptionMessage Argument must be set array. type is invalid:string
     */
    public function testConfigInvalidType()
    {
        $config = array(
            'module_manager' => array(
                'modules' => 'abc',
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $moduleManager->getConfig();
    }

    /**
     * @expectedException        Rindow\Container\Exception\InvalidArgumentException
     * @expectedExceptionMessage Argument must be set array. type is invalid:stdClass
     */
    public function testConfigInvalidObject()
    {
        $config = array(
            'module_manager' => array(
                'modules' => new stdClass(),
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $moduleManager->getConfig();
    }

    public function testInitNormal()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                ),
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $serviceManager = $moduleManager->getServiceLocator();
        $this->assertEquals('Rindow\Container\Container', get_class($serviceManager->get('ServiceLocator')));
        $config = $serviceManager->get('config');
        $this->assertEquals('module1', $config['module_setting']['each_setting']['AcmeTest\Module1']);
    }
/*
    public function testInitDi()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $serviceManager = $moduleManager->getServiceLocator();
        $this->assertTrue($serviceManager->has('Di'));
        $this->assertTrue($serviceManager->has('DependencyInjection'));
    }
*/
    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage The Module is not defined:None
     */
    public function testRunNotfoundModule()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                ),
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $moduleManager->run('None');
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage The Module do not have invokable method for invokables configuration:AcmeTest\Module1
     */
    public function testRunNotfoundRunMethod()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                ),
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $moduleManager->run('AcmeTest\Module1\Module');
    }

    public function testRunNormalAllDefault()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
                'enableCache' => false,
            ),
            'global_config' => array(
                'execute'    => 'moduleTestRunNormal',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $this->assertEquals('AcmeTest\Module2\Module', $moduleManager->run('AcmeTest\Module2\Module'));
    }

    public function testRunNormalExplicitSelfClassName()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
                'invokables' => array(
                    'AcmeTest\Module2\Module' => 'self',
                ),
                'enableCache' => false,
            ),
            'global_config' => array(
                'execute'    => 'moduleTestRunNormal',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $this->assertEquals('AcmeTest\Module2\Module', $moduleManager->run('AcmeTest\Module2\Module'));
    }

    public function testRunNormalExplicitClassNameInServiceManager()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
                'invokables' => array(
                    'AcmeTest\Module2\Module' => 'AcmeTest\Module2\AutorunTestInjection',
                ),
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $this->assertEquals('AcmeTest\Module2\AutorunTestInjection', $moduleManager->run('AcmeTest\Module2\Module'));
    }

    public function testRunNormalExplicitClassNameInServiceManager2()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
                'invokables' => array(
                    'AcmeTest\Module2\Module' => array(
                        'class' => 'AcmeTest\Module2\AutorunTestInjection',
                    ),
                ),
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $this->assertEquals('AcmeTest\Module2\AutorunTestInjection', $moduleManager->run('AcmeTest\Module2\Module'));
    }

    public function testRunNormalExplicitClassNameInDi()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
                'invokables' => array(
                    'AcmeTest\Module2\Module' => array(
                        'class' => 'AcmeTest\Module2\AutorunTest',
                    ),
                ),
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $this->assertEquals('AcmeTest\Module2\AutorunTestInjection', $moduleManager->run('AcmeTest\Module2\Module'));
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage The Module do not have invokable method for invokables configuration:AcmeTest\Module2
     */
    public function testRunNormalExplicitClassNameInDiOtherMethod()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
                'invokables' => array(
                    'AcmeTest\Module2\Module' => array(
                        'class' => 'AcmeTest\Module2\AutorunTest',
                        'method' => 'none',
                    ),
                ),
                'enableCache' => false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $this->assertEquals('AcmeTest\Module2\AutorunTestInjection', $moduleManager->run('AcmeTest\Module2\Module'));
    }

    public function testRunGetServiceLocator()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
                'enableCache' => false,
            ),
            'global_config' => array(
                'execute'    => 'moduleTestRunGetServiceLocator',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $serviceManager = $moduleManager->getServiceLocator();
        $result = $moduleManager->run('AcmeTest\Module2\Module');

        $smid = spl_object_hash($serviceManager);
        $resid = spl_object_hash($result);

        $this->assertEquals($smid,$resid);
    }
/*
    public function testRunGetDi()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
            ),
            'global_config' => array(
                'execute'    => 'moduleTestRunGetDi',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $di = $moduleManager->run('AcmeTest\Module2\Module');
        $this->assertTrue(is_object($di));
        $this->assertEquals('Rindow\Container\Di',get_class($di));

        $serviceManager = $moduleManager->getServiceLocator();
        $im = $di->getServiceManager();
        $smid = spl_object_hash($serviceManager);
        $imid = spl_object_hash($im);

        $this->assertEquals($smid,$imid);
    }
*/
    public function testRunConfigInjection()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'AcmeTest\Module1\Module' => true,
                    'AcmeTest\Module2\Module' => true,
                ),
                'invokables' => array(
                    'AcmeTest\Module2\Module' => array(
                        'class' => 'AcmeTest\Module2\AutorunTestInjection',
                        'config_injector' => 'setConfig',
                    ),
                ),
                'enableCache' => false,
            ),
            'service_manager' => array(
                'factories' => array(
                    'AcmeTest\Module2\AutorunTestInjection' => 'self',
                ),
            ),
            'testRunConfigInjection' => array(
                'response' => 'RunRunSetConfig',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $this->assertEquals('RunRunSetConfig', $moduleManager->run('AcmeTest\Module2\Module'));
    }

    public function testFilters()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                ),
                'filters' => array(
                    __NAMESPACE__.'\TestFilter::doSomething'=>true,
                ),
                'enableCache' => false,
            ),
            'testconfig' => array(
                'something' => 'foo',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $result = array(
            'module_manager' => array(
                'modules' => array(
                ),
                'filters' => array(
                    __NAMESPACE__.'\TestFilter::doSomething'=>true,
                ),
                'enableCache' => false,
            ),
            'testconfig' => array(
                'something' => 'foo',
            ),
            'test_additional' => 'foo',
        );
        $this->assertEquals($result,$moduleManager->getConfig());
    }

    public function testConfigCache()
    {
        $cacheConfig = $this->getCacheConfig();
        $cacheFactory = new ConfigCacheFactory($cacheConfig['cache']);

        $config = array(
            'module_manager' => array(
                'modules' => array(
                ),
                //'enableCache' => true, // Default=true
            ),
            'testconfig' => array(
                'something' => 'foo',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $moduleManager->setConfigCacheFactory($cacheFactory);
        $this->assertEquals($config,$moduleManager->getConfig());

        $moduleManager = new ModuleManager(array(
            'module_manager' => array(
                'modules' => array(
                ),
                //'enableCache' => true, // Default=true
            ),
        ));
        $moduleManager->setConfigCacheFactory($cacheFactory);

        $this->assertEquals($config,$moduleManager->getConfig());
    }

    public function testCheckDependencyWithoutVersion()
    {
        $cacheConfig = $this->getCacheConfig();
        $cacheFactory = new ConfigCacheFactory($cacheConfig['cache']);
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    __NAMESPACE__.'\\TestCheckDependencyModule'=>true,
                ),
                //'enableCache' => true, // Default=true
            ),
            'testconfig' => array(
                'something' => 'foo',
            ),
        );

        TestCheckDependencyModule::$checked = false;
        $moduleManager = new ModuleManager($config);
        $moduleManager->setConfigCacheFactory($cacheFactory);
        $this->assertEquals($config,$moduleManager->getConfig());
        $this->assertTrue(TestCheckDependencyModule::$checked);

        TestCheckDependencyModule::$checked = false;
        $moduleManager = new ModuleManager($config);
        $moduleManager->setConfigCacheFactory($cacheFactory);
        $this->assertEquals($config,$moduleManager->getConfig());
        $this->assertFalse(TestCheckDependencyModule::$checked);
    }

    public function testCheckDependencyWithVersion()
    {
        $cacheConfig = $this->getCacheConfig();
        $cacheFactory = new ConfigCacheFactory($cacheConfig['cache']);
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    __NAMESPACE__.'\\TestCheckDependencyModule'=>true,
                ),
                'version' => 1,
                //'enableCache' => true, // Default=true
            ),
            'testconfig' => array(
                'something' => 'foo',
            ),
        );

        TestCheckDependencyModule::$checked = false;
        $moduleManager = new ModuleManager($config);
        $moduleManager->setConfigCacheFactory($cacheFactory);
        $this->assertEquals($config,$moduleManager->getConfig());
        $this->assertTrue(TestCheckDependencyModule::$checked);

        TestCheckDependencyModule::$checked = false;
        $moduleManager = new ModuleManager($config);
        $moduleManager->setConfigCacheFactory($cacheFactory);
        $this->assertEquals($config,$moduleManager->getConfig());
        $this->assertFalse(TestCheckDependencyModule::$checked);

        TestCheckDependencyModule::$checked = false;
        $config['module_manager']['version'] = 2;
        $moduleManager = new ModuleManager($config);
        $moduleManager->setConfigCacheFactory($cacheFactory);
        $this->assertEquals($config,$moduleManager->getConfig());
        $this->assertTrue(TestCheckDependencyModule::$checked);

        TestCheckDependencyModule::$checked = false;
        $moduleManager = new ModuleManager($config);
        $moduleManager->setConfigCacheFactory($cacheFactory);
        $this->assertEquals($config,$moduleManager->getConfig());
        $this->assertFalse(TestCheckDependencyModule::$checked);
    }

    public function testImports()
    {
        $config = $this->getCacheConfig();
        $config = array_replace_recursive($config,array(
            'module_manager' => array(
                'modules' => array(
                    'Foo\Bar\Module' => true,
                ),
                'imports' => array(
                    __DIR__.'/../../resources/imports/modules' => '@\.php$@',
                ),
            ),
        ));
        $moduleManager = new ModuleManager($config);
        $margedConfig = $moduleManager->getConfig();

        $config = $this->getCacheConfig();
        $namespace = 'AcmeTest\\Module1';
        $result = require __DIR__.'/../../resources/AcmeTest/Module1/Resources/config/module.config.php';
        $config = array_replace_recursive($result,$config);
        $config = array_replace_recursive(array(
            'module_manager' => array(
                'modules' => array(
                    'Foo\Bar\Module' => false,
                    'AcmeTest\Module1\Module' => true,
                ),
                'imports' => array(
                    __DIR__.'/../../resources/imports/modules' => '@\.php$@',
                ),
            ),
        ),$config);
        $this->assertEquals($config,$margedConfig);
    }
}
