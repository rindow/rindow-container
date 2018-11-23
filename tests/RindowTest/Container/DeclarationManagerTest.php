<?php
namespace RindowTest\Container\DeclarationManagerTest;

use PHPUnit\Framework\TestCase;
use Rindow\Annotation\AnnotationManager;

// Test Target Classes
use Rindow\Container\DeclarationManager;
use Rindow\Container\Annotation\Inject;
use Rindow\Container\Annotation\Named;


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
    public function __construct(Param1 $arg1, Param2 $arg2)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
}

/**
* @Named("named0")
*/
class NamedInjection
{
    /**
    * @Inject({@Named(parameter="arg1",value="RindowTest\Container\DeclarationManagerTest\Param0")})
    */
    public function __construct($arg1)
    {
        $this->arg1 = $arg1;
    }
    public function getArg1()
    {
        return $this->arg1;
    }
}
class Test extends TestCase
{
    public function setUp()
    {
    }

    public function testNoConstructor()
    {
        $mgr = new DeclarationManager();
        $mgr->setEnableCache(false);
        $def = $mgr->getDeclaration(__NAMESPACE__.'\Param0');

        $injects = $def->getInjects();

        $this->assertTrue(is_array($injects));
        $this->assertEquals(0, count($injects));
    }

    public function testConstructorArg1()
    {
        $mgr = new DeclarationManager();
        $mgr->setEnableCache(false);
        $def = $mgr->getDeclaration(__NAMESPACE__.'\Param1');

        $injects = $def->getInjects();

        $this->assertTrue(is_array($injects));
        $this->assertEquals(1, count($injects));
        $this->assertEquals(1, count($injects['__construct']));
        $this->assertTrue(array_key_exists('arg1',$injects['__construct']));
        $this->assertEquals(__NAMESPACE__.'\Param0', $injects['__construct']['arg1']['ref']);
    }

    public function testConstructorArg1NonDef()
    {
        $mgr = new DeclarationManager();
        $mgr->setEnableCache(false);
        $def = $mgr->getDeclaration(__NAMESPACE__.'\Param2');

        $injects = $def->getInjects();

        $this->assertTrue(is_array($injects));
        $this->assertEquals(1, count($injects['__construct']));
        $this->assertTrue(array_key_exists('arg1',$injects['__construct']));
        $this->assertEquals(array(), $injects['__construct']['arg1']);
    }

    public function testConstructorArg2()
    {
        $mgr = new DeclarationManager();
        $mgr->setEnableCache(false);
        $def = $mgr->getDeclaration(__NAMESPACE__.'\Param3');

        $injects = $def->getInjects();

        $this->assertTrue(is_array($injects));
        $this->assertEquals(2, count($injects['__construct']));
        $this->assertTrue(array_key_exists('arg1',$injects['__construct']));
        $this->assertEquals(__NAMESPACE__.'\Param1', $injects['__construct']['arg1']['ref']);
        $this->assertTrue(array_key_exists('arg2',$injects['__construct']));
        $this->assertEquals(__NAMESPACE__.'\Param2', $injects['__construct']['arg2']['ref']);
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage RindowTest\Container\DeclarationManagerTest\NotExist does not exist
     */
    public function testNotExist()
    {
        $mgr = new DeclarationManager();
        $mgr->setEnableCache(false);
        $def = $mgr->getDeclaration(__NAMESPACE__.'\NotExist');
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage RindowTest\Container\DeclarationManagerTest\NotExist does not defined
     */
    public function testNotDefined()
    {
        $mgr = new DeclarationManager();
        $mgr->setEnableCache(false);
        $mgr->setRuntimeComplie(false);
        $def = $mgr->getDeclaration(__NAMESPACE__.'\NotExist');
    }

    public function testNamedInjection()
    {
        $mgr = new DeclarationManager();
        $mgr->setAnnotationManager(new AnnotationManager());
        $mgr->setEnableCache(false);
        $def = $mgr->getDeclaration(__NAMESPACE__.'\NamedInjection');
        $exp = array(
            'class' => __NAMESPACE__.'\\NamedInjection',
            'name' => 'named0',
            'injects' => array (
                '__construct' => array (
                    'arg1' => array('ref'=>__NAMESPACE__.'\\Param0'),
                ),
            ),
        );
        $this->assertEquals($exp,$def->export());
    }
    ///**
    // * @expectedException        Rindow\Container\Exception\DomainException
    // * @expectedExceptionMessage Rindow2DiDeclarationMgrTestParam0 is already defined
    // */
    //public function testDuplicate()
    //{
    //    $def = new Rindow\Container\Declaration('Rindow2DiDeclarationMgrTestParam0');
    //    $mgr = new Rindow\Container\DeclarationManager();
    //    $mgr->addDeclaration('Rindow2DiDeclarationMgrTestParam0',$def);
    //    $mgr->addDeclaration('Rindow2DiDeclarationMgrTestParam0',$def);
    //}

    ///public function testExpoertAndLoad()
    //{
    //    $mgr = new Rindow\Container\DeclarationManager();
    //    $def = $mgr->getDeclaration('Rindow2DiDeclarationMgrTestParam0');
    //    $def = $mgr->getDeclaration('Rindow2DiDeclarationMgrTestParam1');
    //    $def = $mgr->getDeclaration('Rindow2DiDeclarationMgrTestParam2');
    //    $def = $mgr->getDeclaration('Rindow2DiDeclarationMgrTestParam3');

    //    $config = $mgr->export();
    //    //echo var_export($config);

    //    $mgr = new Rindow\Container\DeclarationManager();
    //    $mgr->import($config);

    //}
}
