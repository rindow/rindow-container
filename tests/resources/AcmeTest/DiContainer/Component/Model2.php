<?php
namespace AcmeTest\DiContainer\Component;

use Rindow\Container\Annotation\Named;
use Rindow\Container\Annotation\Inject;
use Rindow\Stdlib\Entity\AbstractEntity;

/**
* @Named("model2")
*/
class Model2 extends AbstractEntity
{
	/**
	* @Inject({@Named("model1")})
	*/
	protected $model1;
}
