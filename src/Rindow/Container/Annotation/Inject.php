<?php
namespace Rindow\Container\Annotation;

use Rindow\Stdlib\Entity\AbstractPropertyAccess;

/**
* The annotated method is used as injector.
*
* @Annotation
* @Target({ FIELD,METHOD })
*/
class Inject extends AbstractPropertyAccess
{
    /**
    * @var array  list of pair that is including a variable name and a reference for parmeters. 
    */
    public $value;
}