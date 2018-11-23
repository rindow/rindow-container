<?php
namespace Rindow\Container\Annotation;

use Rindow\Stdlib\Entity\AbstractPropertyAccess;

/**
* The annotated method will be called after injection process.
*
* @Annotation
* @Target({ METHOD })
*/
class PostConstruct extends AbstractPropertyAccess
{
}