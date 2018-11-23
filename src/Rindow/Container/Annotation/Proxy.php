<?php
namespace Rindow\Container\Annotation;

use Rindow\Stdlib\Entity\AbstractPropertyAccess;

/**
* it will be used annotated mode when it create a instance with proxy.
*
* @Annotation
* @Target({ TYPE })
*/
class Proxy extends AbstractPropertyAccess
{
    public $value = true;
}