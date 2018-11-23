<?php
namespace Rindow\Container\Annotation;

use Rindow\Stdlib\Entity\AbstractPropertyAccess;

/**
* The annotated class is allowed to access by alias from injector.
* And The annotated parameter of injector is referred to object by 
* alias.
*
* @Annotation
* @Target({ TYPE,FIELD,METHOD,PARAMETER })
*/
class Named extends AbstractPropertyAccess
{
    /**
    * @var string  alias of class 
    */
    public $value;

    /**
    * @var string  variable name of parameter in a injector method.
    */
    public $parameter;
}
