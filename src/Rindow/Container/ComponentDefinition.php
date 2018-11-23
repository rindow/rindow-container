<?php
namespace Rindow\Container;

use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use Rindow\Container\Exception;
use Rindow\Container\ConfigurationFactory;
use Rindow\Annotation\AnnotationManager;
use Rindow\Container\Annotation\Inject;
use Rindow\Container\Annotation\Named;
use Rindow\Container\Annotation\NamedConfig;
use Rindow\Container\Annotation\NamedIn;
use Rindow\Container\Annotation\Scope;
use Rindow\Container\Annotation\Lazy;
use Rindow\Container\Annotation\Proxy;
use Rindow\Container\Annotation\PostConstruct;

class ComponentDefinition
{
    const PROPERTY_ACCESS_POLICY = 'Rindow\Stdlib\Entity\PropertyAccessPolicy';

    protected $name;
    protected $className;
    protected $injects;
    protected $factory;
    protected $factoryArgs;
    protected $scope;
    protected $initMethod;
    protected $lazy;
    protected $proxyMode;

    public function __construct($classOrConfig=null,$annotationManager=null)
    {
        if($classOrConfig===null) {
            return;
        } else if(is_array($classOrConfig)) {
            $this->setConfig($classOrConfig);
        } else if(is_string($classOrConfig) ||
                (is_object($classOrConfig) && $classOrConfig instanceof ReflectionClass)) {
            $this->compile($classOrConfig,$annotationManager);
        } else {
            throw new Exception\InvalidArgumentException('A configuration is invalid type value');
        }
    }

    public function setConfig(array $config)
    {
        if(isset($config['name']))
            $this->name = $config['name'];
        if(isset($config['class']))
            $this->className = $config['class'];
        if(isset($config['injects']))
            $this->injects = $config['injects'];
        if(isset($config['constructor_args']))
            $this->setConstructorArgs($config['constructor_args']);
        if(isset($config['properties']))
            $this->setProperties($config['properties']);
        if(isset($config['factory']))
            $this->factory = $config['factory'];
        if(isset($config['factory_args']))
            $this->factoryArgs = $config['factory_args'];
        if(isset($config['scope']))
            $this->scope = $config['scope'];
        if(isset($config['init_method']))
            $this->initMethod = $config['init_method'];
        if(isset($config['lazy']))
            $this->lazy = $config['lazy'];
        if(isset($config['proxy']))
            $this->proxyMode = $config['proxy'];
    }

    public function export()
    {
        $definition['class'] = $this->className;
        if($this->name)
            $definition['name'] = $this->name;
        $definition['injects'] = $this->injects;
        if($this->scope)
            $definition['scope'] = $this->scope;
        if($this->initMethod)
            $definition['init_method'] = $this->initMethod;
        return $definition;
    }

    protected function setConstructorArgs(array $properties)
    {
        $this->injects['__construct'] = $properties;
    }

    protected function setProperties(array $properties)
    {
        foreach ($properties as $name => $arg) {
            $this->injects['set'.ucfirst($name)] = array($name => $arg);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getInjects()
    {
        if($this->injects==null)
            return array();
        return $this->injects;
    }

    public function getInject($name)
    {
        if(!isset($this->injects[$name]))
            return false;
        return $this->injects[$name];
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getInitMethod()
    {
        return $this->initMethod;
    }

    public function isLazy()
    {
        return ($this->lazy==true);
    }

    public function getProxyMode()
    {
        return $this->proxyMode;
    }

    public function hasFactory()
    {
        return isset($this->factory);
    }

    public function getFactory()
    {
        if($this->factory==null)
            return null;
        return $this->factory;
    }

    public function getFactoryArgs()
    {
        return $this->factoryArgs;
    }

    public function hasClosureFactory()
    {
        if(isset($this->factory) && !is_string($this->factory))
            return true;
        return false;
    }

    public function addPropertyWithReference($name,$ref)
    {
        if(!is_string($ref))
            throw new Exception\InvalidArgumentException('referenece must be a string as class name or compornent name.');
        $this->injects['set'.ucfirst($name)][$name][InjectType::ARGUMENT_REFERENCE] = $ref;
        unset($this->injects['set'.ucfirst($name)][$name][InjectType::ARGUMENT_VALUE]);
    }

    public function addPropertyWithValue($name,$value)
    {
        $this->injects['set'.ucfirst($name)][$name][InjectType::ARGUMENT_VALUE] = $value;
        unset($this->injects['set'.ucfirst($name)][$name][InjectType::ARGUMENT_REFERENCE]);
    }

    public function addConstructorArgWithReference($name,$ref)
    {
        if(!is_string($ref))
            throw new Exception\InvalidArgumentException('referenece must be a string as class name or compornent name.');
        $this->injects['__construct'][$name][InjectType::ARGUMENT_REFERENCE] = $ref;
        unset($this->injects['__construct'][$name][InjectType::ARGUMENT_VALUE]);
    }

    public function addConstructorArgWithValue($name,$value)
    {
        $this->injects['__construct'][$name][InjectType::ARGUMENT_VALUE] = $value;
        unset($this->injects['__construct'][$name][InjectType::ARGUMENT_REFERENCE]);
    }

    protected function compile($class,$annotationManager=null)
    {
        if(is_string($class)) {
            $this->className = $class;
            try {
                $reflection = new ReflectionClass($this->className);
            } catch(ReflectionException $e) {
                throw new Exception\DomainException($this->className.' does not exist',0,$e);
            }
        } else {
            $this->className = $class->getName();
            $reflection = $class;
        }
        $this->injects = array();

        $methodRef = $reflection->getConstructor();
        if($methodRef && !($methodRef->getDeclaringClass()->isInternal())) {
            $this->injects[$methodRef->name] = $this->compileParameters($methodRef);
        }

        if($annotationManager) {
            $this->mergeAnnotations($annotationManager,$reflection);
        }
    }

    protected function compileParameters($methodRef)
    {
        $params = array();
        $paramRefs = $methodRef->getParameters();
        foreach($paramRefs as $paramRef) {
            $param = array();
            $paramName = $paramRef->name;
            try {
                $paramClassRef = $paramRef->getClass();
                if($paramClassRef)
                    $param[InjectType::ARGUMENT_REFERENCE] = $paramClassRef->getName();
                if($paramRef->isOptional()) {
                    $param[InjectType::ARGUMENT_DEFAULT] = $paramRef->getDefaultValue();
                }
            } catch(ReflectionException $e) {
                throw new Exception\DomainException('invalid type of parameter "'.$paramName.'". reason: '.$e->getMessage().' : '.$methodRef->getFileName().'('.$methodRef->getStartLine().')',0,$e);
            }
            $params[$paramName] = $param;
        }
        return $params;
    }

    protected function mergeAnnotations($annotationManager,$classRef)
    {
        $annotations = $annotationManager->getClassAnnotations($classRef);
        foreach ($annotations as $annotation) {
            if($annotation instanceof Named) {
                if($annotation->value)
                    $this->name = $annotation->value;
                else
                    $this->name = $classRef->name;
            }
            if($annotation instanceof Scope)
                $this->scope = $annotation->value;
            if($annotation instanceof Lazy)
                $this->lazy = true;
            if($annotation instanceof Proxy)
                $this->proxyMode = $annotation->value;
        }
        foreach ($classRef->getProperties() as $propRef) {
            $annotations = $annotationManager->getPropertyAnnotations($propRef);
            foreach ($annotations as $annotation) {
                if(!($annotation instanceof Inject))
                    continue;
                $setter = 'set'.ucfirst($propRef->name);
                if($classRef->hasMethod($setter)) {
                    $params = $this->compileParameters($classRef->getMethod($setter));
                } else {
                    if($classRef->hasMethod('__call') || $classRef->isSubclassOf(self::PROPERTY_ACCESS_POLICY))
                        $params = array($propRef->name => array());
                    else
                        throw new Exception\DomainException('setter is not found to inject for "'.$propRef->name.'": '.$classRef->getFilename().'('.$classRef->getStartLine().')');
                }
                if($annotation->value) {
                    foreach ($annotation->value as $named) {
                        if($named instanceof Named) {
                            if($named->parameter)
                                $params[$named->parameter][InjectType::ARGUMENT_REFERENCE] = $named->value;
                            else
                                $params[$propRef->name][InjectType::ARGUMENT_REFERENCE] = $named->value;
                        } elseif($named instanceof NamedConfig) {
                            if($named->parameter)
                                $params[$named->parameter][InjectType::ARGUMENT_CONFIG] = $named->value;
                            else
                                $params[$propRef->name][InjectType::ARGUMENT_CONFIG] = $named->value;
                        } elseif($named instanceof NamedIn) {
                            if($named->parameter)
                                $params[$named->parameter][InjectType::ARGUMENT_REFERENCE_IN_CONFIG] = $named->value;
                            else
                                $params[$propRef->name][InjectType::ARGUMENT_REFERENCE_IN_CONFIG] = $named->value;
                        }
                    }
                }
                $this->injects[$setter] = $params;
            }
        }

        foreach ($classRef->getMethods() as $methodRef) {
            $annotations = $annotationManager->getMethodAnnotations($methodRef);
            foreach ($annotations as $annotation) {
                if($annotation instanceof PostConstruct) {
                    if($this->initMethod)
                        throw new Exception('@PostConstruct is used twice or more.: '.$methodRef->getFilename().'('.$methodRef->getStartLine().')');
                    $this->initMethod = $methodRef->name;
                    continue;
                }
                if(!($annotation instanceof Inject))
                    continue;
                if('__construct' == $methodRef->name)
                    $params = $this->injects['__construct'];
                else
                    $params = $this->compileParameters($methodRef);
                if($annotation->value) {
                    foreach ($annotation->value as $named) {
                        if($named instanceof Named) {
                            if($named->parameter==null)
                                throw new Exception\DomainException('argument name is not found specified for @Named on "'.$methodRef->name.'": '.$methodRef->getFilename().'('.$methodRef->getStartLine().')');
                            $params[$named->parameter][InjectType::ARGUMENT_REFERENCE] = $named->value;
                        } elseif($named instanceof NamedConfig) {
                            if($named->parameter==null)
                                throw new Exception\DomainException('argument name is not found specified for @NamedConfig on "'.$methodRef->name.'": '.$methodRef->getFilename().'('.$methodRef->getStartLine().')');
                            $params[$named->parameter][InjectType::ARGUMENT_CONFIG] = $named->value;
                        } elseif($named instanceof NamedIn) {
                            if($named->parameter==null)
                                throw new Exception\DomainException('argument name is not found specified for @NamedConfig on "'.$methodRef->name.'": '.$methodRef->getFilename().'('.$methodRef->getStartLine().')');
                            $params[$named->parameter][InjectType::ARGUMENT_REFERENCE_IN_CONFIG] = $named->value;
                        }
                    }
                }
                $this->injects[$methodRef->name] = $params;
            }
        }
    }

    public function addMethodDeclaration($methodName)
    {
        $methodRef = null;
        try {
            $reflection = new ReflectionClass($this->className);
            if($reflection->hasMethod($methodName))
                $methodRef = $reflection->getMethod($methodName);
        } catch(ReflectionException $e) {
            throw new Exception\DomainException($this->className.' does not exist',0,$e);
        }

        if($methodRef && !($methodRef->getDeclaringClass()->isInternal())) {
            $this->injects[$methodName] = $this->compileParameters($methodRef);
            return true;
        } else {
            return false;
        }
    }

    public function addMethodDeclarationForce($methodName,$paramName,$reference=null)
    {
        if($reference)
            $this->injects[$methodName][$paramName][InjectType::ARGUMENT_REFERENCE] = $reference;
        else
            $this->injects[$methodName][$paramName] = array();
    }
}