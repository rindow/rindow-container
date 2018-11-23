<?php
namespace Rindow\Container\Annotation;

use Rindow\Stdlib\Entity\AbstractPropertyAccess;

/**
* The annotated parameter of injecter is referred to object by 
* alias in the config.
*
* @Annotation
* @Target({ FIELD,METHOD,PARAMETER })
*/
class NamedIn extends AbstractPropertyAccess
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
