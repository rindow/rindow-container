<?php
namespace Rindow\Container\Annotation;

use Rindow\Stdlib\Entity\AbstractPropertyAccess;

/**
* The annotated class will be created to instance as defined lifecycle.
*
* @Annotation
* @Target({ TYPE })
*/
class Scope extends AbstractPropertyAccess
{
    /**
    * @Enum("singleton","prototype","request","session","global_session")
    */
    public $value;
}