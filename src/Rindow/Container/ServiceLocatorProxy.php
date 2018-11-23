<?php
namespace Rindow\Container;

class ServiceLocatorProxy /* implements ServiceLocator */
{
    protected $oppositeServiceLocator;

    public function __construct($oppositeServiceLocator)
    {
        $this->oppositeServiceLocator = $oppositeServiceLocator;
    }

    public function get($className,array $options=null)
    {
        $object = $this->oppositeServiceLocator->get($className,$options);
        if($object===null && !$this->has($className))
            throw new Exception\DomainException('Undefined component.: '.$className);
        return $object;
    }

    public function has($className)
    {
        return $this->oppositeServiceLocator->has($className);
    }
}
