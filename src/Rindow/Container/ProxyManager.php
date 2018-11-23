<?php
namespace Rindow\Container;

interface ProxyManager
{
    public function newProxy(Container $container,ComponentDefinition $component);
}
