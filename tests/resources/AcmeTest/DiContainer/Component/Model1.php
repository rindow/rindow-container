<?php
namespace AcmeTest\DiContainer\Component;

use Rindow\Container\Annotation\Named;
use Rindow\Container\Annotation\Inject;

/**
* @Named("model1")
*/
class Model1
{
	protected $model0;

	/**
	* @Inject({@Named(parameter="model0",value="model0")})
	*/
	public function __construct($model0)
	{
		$this->model0 = $model0;
	}
	public function getModel0()
	{
		return $this->model0;
	}
}
