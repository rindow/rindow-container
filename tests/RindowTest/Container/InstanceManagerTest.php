<?php
namespace RindowTest\Container\InstanceManagerTest;

use PHPUnit\Framework\TestCase;
// Test Target Classes
use Rindow\Container\InstanceManager;
class CreateInstance0
{
}
class CreateInstance1
{
}

class ServiceManagerTest extends TestCase
{
    public function setUp()
    {
    }

    public function testSetGetHas()
    {
        $im = new InstanceManager();
        $this->assertFalse($im->has('foo'));
        $this->assertFalse($im->get('foo'));
        $im->setInstance('foo',new CreateInstance0());
        $this->assertTrue($im->has('foo'));
        $this->assertEquals(__NAMESPACE__.'\CreateInstance0',get_class($im->get('foo')));
    }

    /**
     * @expectedException        Rindow\Container\Exception\DomainException
     * @expectedExceptionMessage Already registered:foo
     */
    public function testSetDuplicate()
    {
        $im = new InstanceManager();
        $im->setInstance('foo',new CreateInstance0());
        $im->setInstance('foo',new CreateInstance0());
    }
}
