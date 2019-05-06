<?php
namespace RindowTest\Container\ComponentDefinitionTest;

use PHPUnit\Framework\TestCase;
use Rindow\Annotation\AnnotationManager;

use Rindow\Stdlib\Entity\AbstractEntity;
use Rindow\Stdlib\Entity\PropertyAccessPolicy;

// Test Target Classes
use Rindow\Container\ComponentDefinition;
use Rindow\Container\Annotation\Inject;
use Rindow\Container\Annotation\Named;
use Rindow\Container\Annotation\NamedConfig;
use Rindow\Container\Annotation\Scope;
use Rindow\Container\Annotation\PostConstruct;

class Param0
{
}
class Param1
{
    public function __construct(Param0 $arg1)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
}
class Param2
{
    public function __construct($arg1)
    {
        $this->arg1 = $arg1;
    }
}

class Param3
{
    public function __construct(Param1 $arg1,Param2 $arg2)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
}

class Param4
{
    public function __construct(Hogehoge $arg1)
    {
    }
}

class Param5
{
    public function __construct($arg1=null)
    {
        $this->arg1 = $arg1;
    }
}

class Param6
{
    const TESTCONST = 100;
    public function __construct($arg1=self::TESTCONST)
    {
        $this->arg1 = $arg1;
    }
}

class Param7
{
    public function __construct(Param0 $arg1=null)
    {
        $this->arg1 = $arg1;
    }
}

/**
* @Named("named0")
*/
class NamedInjection
{
    /**
    * @Inject({@Named(parameter="arg1",value="RindowTest\Container\ComponentDefinitionTest\Param0")})
    */
    public function __construct($arg1=null)
    {
        $this->arg1 = $arg1;
    }
    public function getArg1()
    {
        return $this->arg1;
    }
}
class SetterInjection
{
    /**
    * @Inject
    */
    public function setArg1(Param0 $arg1)
    {
        $this->arg1 = $arg1;
    }
    public function getArg1()
    {
        return $this->arg1;
    }
    // no setter
    public function setArg2(Param0 $arg2)
    {
        $this->arg2 = $arg2;
    }
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

class SetterNamedInjection
{
    /**
    * @Inject({@Named(parameter="arg1",value="RindowTest\Container\ComponentDefinitionTest\Param0")})
    */
    public function setArg1($arg1)
    {
        $this->arg1 = $arg1;
    }
    public function getArg1()
    {
        return $this->arg1;
    }
}
class FieldAnnotationInjection
{
    /**
    * @Inject
    */
    protected $arg0;
    public function setArg0(Param0 $arg0)
    {
        $this->arg0 = $arg0;
    }
}
class FieldAnnotationNamedInjection
{
    /**
    * @Inject({@Named("RindowTest\Container\ComponentDefinitionTest\Param0")})
    */
    protected $arg0;
    public function setArg0($arg0)
    {
        $this->arg0 = $arg0;
    }
}
class FieldAnnotationNamedInjectionAbstractEntity extends AbstractEntity 
{
    /**
    * @Inject({@Named("RindowTest\Container\ComponentDefinitionTest\Param0")})
    */
    protected $arg0;
}
class FieldAnnotationNamedInjectionPropertyAccess implements PropertyAccessPolicy
{
    /**
    * @Inject({@Named("RindowTest\Container\ComponentDefinitionTest\Param0")})
    */
    public $arg0;
}
class FieldAnnotationAndSetterNamedInjection
{
    /**
    * @Inject
    */
    protected $arg0;
    /**
    * @Inject({@Named(parameter="arg0",value="RindowTest\Container\ComponentDefinitionTest\Param0")})
    */
    public function setArg0($arg0)
    {
        $this->arg0 = $arg0;
    }
}
class FieldAnnotationInjectionSetterNotfound
{
    /**
    * @Inject
    */
    protected $arg0;
    public function setArgOther($arg0)
    {
        $this->arg0 = $arg0;
    }
}
class ComplexInjection
{
    /**
    * @Inject({@Named(parameter="arg0",value="RindowTest\Container\ComponentDefinitionTest\Param0")})
    */
    public function __construct($arg0,Param1 $arg1)
    {
        $this->arg0 = $arg0;
        $this->arg1 = $arg1;
    }
    /**
    * @Inject({@Named(parameter="arg3",value="RindowTest\Container\ComponentDefinitionTest\Param3")})
    */
    public function setArg2(Param2 $arg2,$arg3)
    {
        $this->arg2 = $arg2;
        $this->arg3 = $arg3;
    }
    /**
    * @Inject({@Named(parameter="arg4",value="RindowTest\Container\ComponentDefinitionTest\Param4")})
    */
    public function setArg4($arg4)
    {
        $this->arg4 = $arg4;
    }
}
/**
* @Scope("prototype")
*/
class PrototypeScope
{
}

class PostConstructAnnotation
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

class Test extends TestCase
{
    public function setUp()
    {
    }

    public function testCombination()
    {
        $config = array(
            'name' => __NAMESPACE__.'\Test1Component',
            'class' => __NAMESPACE__.'\Test1',
            'constructor_args' => array(
                'arg0' => array('ref'  =>__NAMESPACE__.'\Test0'),
                'arg1' => array('value'=>'value1'),
            ),
            'properties' => array(
                'prop0' => array('ref'   =>__NAMESPACE__.'\Test0'),
                'prop1' => array('value' =>'value1'),
            ),
            'injects' => array(
                'setter1' => array(
                    'arg10' => array('ref'  => __NAMESPACE__.'\Test10'),
                    'arg11' => array('value'=>'value11'),
                ),
            ),
            'factory' => __NAMESPACE__.'\Test0Factory::factory',
            'factory_args' => array('foo'=>'bar'),
            'init_method' => 'init',
            'scope' => 'prototype',
            'lazy' => true,
        );
        $component = new ComponentDefinition($config);
        $injects = array(
            'setter1' => array(
                'arg10' => array('ref'  => __NAMESPACE__.'\Test10'),
                'arg11' => array('value'=>'value11'),
            ),
            '__construct' => array(
                'arg0' => array('ref'  =>__NAMESPACE__.'\Test0'),
                'arg1' => array('value'=>'value1'),
            ),
            'setProp0' => array(
                'prop0' => array('ref'   =>__NAMESPACE__.'\Test0'),
            ),
            'setProp1' => array(
                'prop1' => array('value' =>'value1'),
            ),
        );
        $this->assertEquals($injects,$component->getInjects());

        $inject = array(
            'arg0' => array('ref'  =>__NAMESPACE__.'\Test0'),
            'arg1' => array('value'=>'value1'),
        );
        $this->assertEquals($inject,$component->getInject('__construct'));
        $this->assertFalse($component->getInject('nonesetter'));
        
        $this->assertTrue($component->hasFactory());
        $this->assertEquals(__NAMESPACE__.'\Test0Factory::factory',$component->getFactory());
        $this->assertFalse($component->hasClosureFactory());

        $this->assertEquals(array('foo'=>'bar'),$component->getFactoryArgs());
        $this->assertEquals(__NAMESPACE__.'\Test1Component',$component->getName());
        $this->assertEquals(__NAMESPACE__.'\Test1',$component->getClassName());
        $this->assertEquals('init',$component->getInitMethod());
        $this->assertEquals('prototype',$component->getScope());
        $this->assertEquals(true,$component->isLazy());
    }

    public function testAddProperty()
    {
        $component = new ComponentDefinition();
        $component->addPropertyWithReference('arg1','Component1');
        $injects = array(
            'setArg1' => array(
                'arg1' => array('ref'  =>'Component1'),
            ),
        );
        $this->assertEquals($injects,$component->getInjects());

        $component->addPropertyWithValue('arg1','value1');
        $injects = array(
            'setArg1' => array(
                'arg1' => array('value'=>'value1'),
            ),
        );
        $this->assertEquals($injects,$component->getInjects());

        $component->addPropertyWithValue('arg2','value2');
        $injects = array(
            'setArg1' => array(
                'arg1' => array('value'=>'value1'),
            ),
            'setArg2' => array(
                'arg2' => array('value'=>'value2'),
            ),
        );
        $this->assertEquals($injects,$component->getInjects());

        $component->addPropertyWithReference('arg2','Component2');
        $injects = array(
            'setArg1' => array(
                'arg1' => array('value'=>'value1'),
            ),
            'setArg2' => array(
                'arg2' => array('ref'  =>'Component2'),
            ),
        );
        $this->assertEquals($injects,$component->getInjects());
    }

    public function testNoConstructor()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\Param0');

        $injects = $def->getInjects();

        $injects = array(
        );
        $this->assertEquals($injects,$def->getInjects());
    }

    public function testConstructorArg1()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\Param1');

        $injects = array(
            '__construct' => array(
                'arg1' => array('ref' => __NAMESPACE__.'\Param0'),
            ),
        );
        $this->assertEquals($injects,$def->getInjects());
    }

    public function testConstructorArg1NonDef()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\Param2');

        $injects = array(
            '__construct' => array(
                'arg1' => array(),
            ),
        );
        $this->assertEquals($injects,$def->getInjects());
    }

    public function testConstructorArg2()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\Param3');

        $injects = array(
            '__construct' => array(
                'arg1' => array('ref' => __NAMESPACE__.'\Param1'),
                'arg2' => array('ref' => __NAMESPACE__.'\Param2'),
            ),
        );
        $this->assertEquals($injects,$def->getInjects());
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage RindowTest\Container\ComponentDefinitionTest\NotExist does not exist
     */
    public function testNotExist()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\NotExist');
    }

    public function testExport()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\Param1');
        $config = array(
            'class' => __NAMESPACE__.'\Param1',
            'injects' => array(
                '__construct' => array(
                    'arg1' => array('ref'=>__NAMESPACE__.'\Param0'),
                ),
            ),
        );
        $this->assertEquals($config,$def->export());

        //echo var_export($config);
    }

    public function testComplieAndLoad()
    {
        $def0 = new ComponentDefinition(__NAMESPACE__.'\Param1');
        $config0 = $def0->export();

        $def = new ComponentDefinition($config0);
        $config = $def->export();
        $this->assertEquals($config0,$config);
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage invalid type of parameter "arg1". reason: Class RindowTest\Container\ComponentDefinitionTest\Hogehoge does not exist :
     */
    public function testNoArgumentType()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\Param4');

        $injects = $def->getInjects();
    }

    public function testDefaultValue()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\Param5');
        $injects = array(
            '__construct' => array(
                'arg1' => array('default' => null),
            ),
        );
        $this->assertEquals($injects,$def->getInjects());
    }

    public function testDefaultValueConstant()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\Param6');
        $injects = array(
            '__construct' => array(
                'arg1' => array('default' => 100),
            ),
        );
        $this->assertEquals($injects,$def->getInjects());
    }

    public function testTypeAndDefaultValue()
    {
        $def = new ComponentDefinition(__NAMESPACE__.'\Param7');
        $injects = array(
            '__construct' => array(
                'arg1' => array(
                    'ref' => __NAMESPACE__.'\Param0',
                    'default' => null,
                ),
            ),
        );
        $this->assertEquals($injects,$def->getInjects());
    }

    public function testNamedInjection()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\NamedInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\NamedInjection',
            'injects' => array (
                '__construct' => array (
                    'arg1' => array(
                        'ref'=>__NAMESPACE__.'\\Param0',
                        'default' => null,
                    ),
                ),
            ),
            'name' => 'named0',
        );
        $this->assertEquals($exp,$def->export());
    }
    public function testSetterInjection()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\SetterInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\SetterInjection',
            'injects' => array (
                'setArg1' => array (
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }
    public function testMultiArgInjection()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\MultiArgInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\MultiArgInjection',
            'injects' => array (
                'setArguments' => array (
                    'arg0' => array('ref'=>__NAMESPACE__.'\\Param0'),
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param1'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }

    public function testSetterNamedInjection()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\SetterNamedInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\SetterNamedInjection',
            'injects' => array (
                'setArg1' => array (
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }
    public function testFieldAnnotationInjection()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\FieldAnnotationInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\FieldAnnotationInjection',
            'injects' => array (
                'setArg0' => array (
                    'arg0' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }
    public function testFieldAnnotationNamedInjection()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\FieldAnnotationNamedInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\FieldAnnotationNamedInjection',
            'injects' => array (
                'setArg0' => array (
                    'arg0' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }
    public function testFieldAnnotationNamedInjectionAbstractEntity()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\FieldAnnotationNamedInjectionAbstractEntity',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\FieldAnnotationNamedInjectionAbstractEntity',
            'injects' => array (
                'setArg0' => array (
                    'arg0' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }
    public function testFieldAnnotationNamedInjectionPropertyAccess()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\FieldAnnotationNamedInjectionPropertyAccess',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\FieldAnnotationNamedInjectionPropertyAccess',
            'injects' => array (
                'setArg0' => array (
                    'arg0' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }
    public function testFieldAnnotationAndSetterNamedInjection()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\FieldAnnotationAndSetterNamedInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\FieldAnnotationAndSetterNamedInjection',
            'injects' => array (
                'setArg0' => array (
                    'arg0' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }
    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage setter is not found to inject for "arg0":
     */
    public function testFieldAnnotationInjectionSetterNotFound()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\FieldAnnotationInjectionSetterNotFound',$annotationManager);
    }
    public function testComplexInjection()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\ComplexInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\ComplexInjection',
            'injects' => array (
                '__construct' => array (
                    'arg0' => array('ref'=>__NAMESPACE__.'\\Param0'),
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param1'),
                ),
                'setArg2' => array (
                    'arg2' => array('ref'=>__NAMESPACE__.'\\Param2'),
                    'arg3' => array('ref'=>__NAMESPACE__.'\\Param3'),
                ),
                'setArg4' => array (
                    'arg4' => array('ref'=>__NAMESPACE__.'\\Param4'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }

    public function testScope()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\PrototypeScope',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\PrototypeScope',
            'injects' => array (
            ),
            'scope' => 'prototype',
        );
        $this->assertEquals($exp,$def->export());
        $this->assertEquals('prototype',$def->getScope());
    }

    public function testPostConstruct()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\PostConstructAnnotation',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\PostConstructAnnotation',
            'injects' => array (
                'setArg0' => array(
                    'arg0' => array('default'=>123),
                )
            ),
            'init_method' => 'init',
        );
        $this->assertEquals($exp,$def->export());
        $this->assertEquals('init',$def->getinitMethod());
    }

    public function testAddMethod()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\SetterInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\SetterInjection',
            'injects' => array (
                'setArg1' => array (
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());

        $this->assertTrue($def->addMethodDeclaration('setArg2'));
        $exp = array(
            'class' => __NAMESPACE__.'\\SetterInjection',
            'injects' => array (
                'setArg1' => array (
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
                'setArg2' => array (
                    'arg2' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());

        $this->assertFalse($def->addMethodDeclaration('none'));
        $this->assertEquals($exp,$def->export());
    }

    public function testAddMethodForce()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\SetterInjection',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\SetterInjection',
            'injects' => array (
                'setArg1' => array (
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());

        $def->addMethodDeclarationForce('none1','argx');
        $exp = array(
            'class' => __NAMESPACE__.'\\SetterInjection',
            'injects' => array (
                'setArg1' => array (
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
                'none1' => array (
                    'argx' => array(),
                ),
            ),
        );

        $def->addMethodDeclarationForce('none2','argy','hoge');
        $exp = array(
            'class' => __NAMESPACE__.'\\SetterInjection',
            'injects' => array (
                'setArg1' => array (
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
                'none1' => array (
                    'argx' => array(),
                ),
                'none2' => array (
                    'argy' => array('ref'=>'hoge'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }

    public function testInjectNamedConfig()
    {
        $annotationManager = new AnnotationManager();
        $def = new ComponentDefinition(__NAMESPACE__.'\InjectNamedConfig',$annotationManager);
        $exp = array(
            'class' => __NAMESPACE__.'\\InjectNamedConfig',
            'injects' => array(
                'setTestValue' => array(
                    'testValue' => array(
                        'config' => 'test::named::config::value',
                    ),
                ),
                'setTestSetter' => array(
                    'testSetter' => array(
                        'config' => 'test::named::config::setter',
                    ),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }
}
