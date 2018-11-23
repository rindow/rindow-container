<?php
namespace Rindow\Container\Annotation;

use Rindow\Stdlib\Entity\AbstractPropertyAccess;

/**
* The annotated parameter of injector is referred to the "config" instance
* in the container by configuration-path.
*
* @Annotation
* @Target({ FIELD,METHOD,PARAMETER })
*/
class NamedConfig extends AbstractPropertyAccess
{
    /**
    * @var string  alias of configuration path
    */
    public $value;

    /**
    * @var string  variable name of parameter in a injector method.
    */
    public $parameter;
}
