<?php
namespace Rindow\Container;

use Rindow\Container\Exception;
use Rindow\Stdlib\Entity\PropertyAccessPolicy;
use Rindow\Annotation\AnnotationManager;
use Psr\Container\ContainerInterface;

use ReflectionClass;

class Container implements ContainerInterface
{
    //const DEFAULT_ANNOTATION_MANAGER = 'Rindow\Annotation\AnnotationManager';
    const DEFAULT_LOGGER = 'Logger';
    const PROXY_MODE_COMPONENT = 'component';
    const PROXY_MODE_EXPLICIT  = 'explicit';
    const PROXY_MODE_ALL       = 'all';

    protected $declarationManager;
    protected $instanceManager;
    protected $parentManager;
    protected $annotationManager;
    protected $proxyManager;
    protected $componentPaths;
    protected $autoProxy = self::PROXY_MODE_COMPONENT;
    protected $isDebug;
    protected $loggerName;
    protected $logger;
    protected $recursionTracking=array();
    protected $implicitComponentNameAsClass = false;

    public function __construct(
        array $config = null,
        ComponentDefinitionManager $componentManager=null,
        DeclarationManager $declarationManager=null,
        InstanceManager $instanceManager=null,
        $cachePath=null)
    {
        if($componentManager)
            $this->componentManager = $componentManager;
        else
            $this->componentManager = new ComponentDefinitionManager($cachePath);
        if($declarationManager)
            $this->declarationManager = $declarationManager;
        else
            $this->declarationManager = new DeclarationManager($cachePath);
        if($instanceManager)
            $this->instanceManager = $instanceManager;
        else
            $this->instanceManager = new InstanceManager();
        if($config!==null)
            $this->setConfig($config);
    }

    public function getComponentManager()
    {
        return $this->componentManager;
    }

    public function getDeclarationManager()
    {
        return $this->declarationManager;
    }

    public function getInstanceManager()
    {
        return $this->instanceManager;
    }

    public function setParentManager(/*ServiceLocator*/ $parentManager)
    {
        $this->parentManager = $parentManager;
        return $this;
    }

    public function getParentManager()
    {
        return $this->parentManager;
    }

    public function setConfig($config)
    {
        if(isset($config['debug']))
            $this->isDebug = $config['debug'];
        if(isset($config['logger']))
            $this->loggerName = $config['logger'];
        if(array_key_exists('annotation_manager',$config)) {
            throw new Exception\DomainException('The annotation_manager option is expired.');
            //$this->setAnnotationManagerName($config['annotation_manager']);
            //$this->declarationManager->setAnnotationManager($this->annotationManager);
            //$this->componentManager->setAnnotationManager($this->annotationManager);
        }
        $this->componentManager->setConfig($config);
        $this->declarationManager->setConfig($config);
        $this->instanceManager->setConfig($config);
        if($this->proxyManager) {
            $this->proxyManager>setConfig($config);
        }
        if(isset($config['component_paths'])) {
            $this->componentPaths = $config['component_paths'];
        }
        if(isset($config['auto_proxy'])) {
            $this->autoProxy = $config['auto_proxy'];
        }
        if(isset($config['implicit_component'])) {
            $this->implicitComponentNameAsClass = $config['implicit_component'];
        }
        return $this;
    }

    public function setProxyManager(ProxyManager $proxyManager)
    {
        $this->proxyManager = $proxyManager;
        return $this;
    }

    public function getProxyManager()
    {
        return $this->proxyManager;
    }
/*
    public function setAnnotationManagerName($annotationManager)
    {
        if($annotationManager===true || $annotationManager === self::DEFAULT_ANNOTATION_MANAGER) {
            $this->annotationManager = AnnotationManager::factory();
        } else if(is_string($annotationManager)) {
            if(class_exists($annotationManager))
                $this->annotationManager = new $annotationManager();
        } else {
            $this->annotationManager = $annotationManager;
        }
        return $this;
    }
*/
    public function setAnnotationManager($annotationManager)
    {
        $this->annotationManager = $annotationManager;
        $this->declarationManager->setAnnotationManager($this->annotationManager);
        $this->componentManager->setAnnotationManager($this->annotationManager);
    }

    public function getAnnotationManager()
    {
        return $this->annotationManager;
    }

    protected function debug($message, array $context = array())
    {
        if(!$this->isDebug)
            return;
        if($this->logger==null) {
            if(isset($this->loggerName))
                $logService = $this->loggerName;
            else
                $logService = self::DEFAULT_LOGGER;
            $this->isDebug = false;
            $this->logger = $this->get($logService);
            $this->isDebug = true;
        }
        $this->logger->debug($message,$context);
    }

    public function scanComponents()
    {
        if($this->componentPaths==null)
            return $this;

        $componentScanner = new ComponentScanner();
        $componentScanner->setAnnotationManager($this->annotationManager);
        $this->componentManager->attachScanner($componentScanner);
        if($this->proxyManager) {
            $this->proxyManager->attachScanner($componentScanner);
        }
        $componentScanner->scan($this->componentPaths);
        return $this;
    }

    public function get($componentName)//,array $options=null)
    {
        if(!is_string($componentName))
            throw new Exception\InvalidArgumentException('Class name must be string');

        if($this->isDebug)
            $this->debug('start to get the component "'.$componentName.'"');
        $componentName = $this->componentManager->resolveAlias($componentName);
        if($componentName==null) {
            return null;
        }

        if(!array_key_exists($componentName, $this->recursionTracking)) {
            $this->recursionTracking[$componentName] = true;
        } else {
            throw new Exception\DomainException('a recursion dependency is detected in "'.$componentName.'"');
        }

        try {
            if($this->isDebug)
                $this->debug('Alias is resolved to "'.$componentName.'"');
            if($this->componentManager->isIgnored($componentName)) {
                if($this->parentManager==null)
                    throw new Exception\DomainException('Ignored component.: '.$componentName);
                if($this->isDebug)
                    $this->debug('the component "'.$componentName.'" is ignored. try to get it from parent manager.');
                $instance = $this->parentManager->get($componentName);
                unset($this->recursionTracking[$componentName]);
                return $instance;
            }
    
            $component = $this->componentManager->getComponent($componentName);
    
            if($this->instanceManager->has($componentName)) {
                if(!$component || $component->getScope()!=InjectType::SCOPE_PROTOTYPE) {
                    if($this->isDebug)
                        $this->debug('a existing instance of "'.$componentName.'" is found.');
                    $instance = $this->instanceManager->get($componentName);
                    unset($this->recursionTracking[$componentName]);
                    return $instance;
                }
            }
    
            $isNamedComponent = false;
            if($component) {
                $isNamedComponent = true;
            } else {
                if($this->isDebug)
                    $this->debug('the component "'.$componentName.'" is not defined. try create from others.');
                if($this->implicitComponentNameAsClass && class_exists($componentName)) {
                    if($this->isDebug)
                        $this->debug('create a component definiton of "'.$componentName.'" by class name.');
                    $component = $this->componentManager->newComponent($componentName);
                } else {
                    if($this->parentManager==null) {
                        throw new Exception\DomainException('Undefined component.: '.$componentName);
                    }
                    if($this->isDebug)
                        $this->debug('the component "'.$componentName.'" is not found. try to get it from parent manager.');
                    $instance = $this->parentManager->get($componentName);
                    unset($this->recursionTracking[$componentName]);
                    return $instance;
                }
            }
    
            if($component->hasFactory()) {
                $declaration = null;
            } else {
                $declaration = $this->declarationManager->getDeclaration($component->getClassName());
                if($declaration->getName())
                    $isNamedComponent = true;
            }
            $autoProxy = false;
            $proxyOptions = null;
            if($this->proxyManager) {
                if($component->isLazy() || ($declaration && $declaration->isLazy())) {
                    $autoProxy = true;
                    $proxyOptions['lazy'] = true;
                } else if($this->autoProxy == self::PROXY_MODE_COMPONENT) {
                    if($isNamedComponent)
                        $autoProxy = true;
                } else if($this->autoProxy == self::PROXY_MODE_EXPLICIT) {
                    if($component->getProxyMode() || ($declaration && $declaration->getProxyMode())) {
                        $autoProxy = true;
                    }
                } else if($this->autoProxy == self::PROXY_MODE_ALL) {
                    $autoProxy = true;
                }
            }
    
            if($autoProxy) {
                $proxyOptions['mode'] = $component->getProxyMode();
                if(!isset($proxyOptions['mode']) && $declaration) {
                    $proxyOptions['mode'] = $declaration->getProxyMode();
                }
                if($this->isDebug)
                    $this->debug('call a proxy manager to handle the component "'.$componentName.'".');
                $instance = $this->proxyManager->newProxy($this,$component,$proxyOptions);
            } else {
                $instance = $this->instantiate($component,$componentName,$declaration);
            }
    
            $scope = null;
            if($declaration)
                $scope = $declaration->getScope();
            if($component->getScope())
                $scope = $component->getScope();
            //if(isset($options['scope']))
            //    $scope = $options['scope'];
            if($scope!=InjectType::SCOPE_PROTOTYPE) {
                if($this->isDebug)
                    $this->debug('register instance of "'.$componentName.'".');
                $this->instanceManager->setInstance($componentName,$instance);
            } else {
                if($this->isDebug)
                    $this->debug('scope of "'.$componentName.'" is prototype. it is not registered.');
            }
            unset($this->recursionTracking[$componentName]);
            return $instance;
        } catch(\Exception $e) {
            unset($this->recursionTracking[$componentName]);
            throw $e;
        }
    }

    public function instantiate(ComponentDefinition $component,$componentName=null,ComponentDefinition $declaration=null,$instance=null,$alternateConstructor=null)
    {
        if($this->isDebug)
            $this->debug('instantiate the component "'.$componentName.'".');
        if($componentName==null)
            $componentName = $component->getName();
        if($component->hasFactory())
            return $this->newInstanceByFactory($component,$componentName);

        if($declaration==null)
            $declaration = $this->declarationManager->getDeclaration($component->getClassName());
        $injects = $declaration->getInjects();

        // Inject by constructor
        if($alternateConstructor==null)
            $alternateConstructor='__construct';
        if(array_key_exists('__construct', $injects)) {
            $params = $this->buildParams($injects['__construct'],$componentName,'__construct',$component->getInject('__construct'));
        } else {
            $params = array();
        }
        if($instance==null)
            $instance = $this->newInstanceByParams($component->getClassName(),$params,$componentName);
        else if(array_key_exists('__construct', $injects)) {
            if($this->isDebug)
                $this->debug('call a alternate constructor of proxy for "'.$componentName.'".');
            call_user_func_array(array($instance,$alternateConstructor), $params);
        }

        // Inject by setter
        $componentInjects = $component->getInjects();
        $injects = array_replace_recursive($injects, $componentInjects);
        foreach($injects as $methodName => $inject) {
            if($methodName==='__construct' || $methodName===$alternateConstructor)
                continue;
            if(isset($componentInjects[$methodName]))
                $componentInject = $componentInjects[$methodName];
            else
                $componentInject = false;
            $params = $this->buildParams($inject,$componentName,$methodName,$componentInject);
            $this->injectBySetter($instance,$methodName,$params);
        }

        $initMethod = $declaration->getInitMethod();
        if($component->getInitMethod())
            $initMethod = $component->getInitMethod();
        if($initMethod) {
            $init = array($instance,$initMethod);
            if(!is_callable($init))
                throw new Exception\DomainException('Invalid initMethod in the component.:'.$componentName);
            if($this->isDebug)
                $this->debug('call a initialization of proxy for "'.$componentName.'".');
            call_user_func($init);
        }
        return $instance;
    }


    public function has($componentName)
    {
        if(!is_string($componentName))
            throw new Exception\InvalidArgumentException('Class name must be string');

        $componentManager = $this->componentManager;
        $instanceManager = $this->instanceManager;

        $componentName = $componentManager->resolveAlias($componentName);
        if($componentName==null)
            return false;
        if($instanceManager->has($componentName))
            return true;
        if($componentManager->hasComponent($componentName))
            return true;

        if(class_exists($componentName))
            return true;
        if($this->parentManager==null)
            return false;
        return $this->parentManager->has($componentName);
    }

    protected function buildParams($inject,$componentName,$methodName,$componentInject)
    {
        if($this->isDebug)
            $this->debug('build paramaters for "'.$componentName.'".');
        $params = array();
        foreach($inject as $paramName => $paramSet) {
            list($type,$param) = $this->parseParam($paramSet,$componentName,$methodName,$paramName);
            if(isset($componentInject[$paramName]))  {
                list($type,$param) = $this->parseParam($componentInject[$paramName],$componentName,$methodName,$paramName);
                // This decision was put on hold.
                // Component definitions defined in config should not have "default" type.
                // But $componentInject in component has "default" type when that component is created by annotation @Named in the ComponentDefinitionManager::getComponent().
                // 
                // if($type===InjectType::ARGUMENT_DEFAULT)
                //     throw new Exception\DomainException('It can not use "default" in a component parameter type:'.$componentName.'::'.$methodName.'( .. $'.$paramName.' .. )');
            }
            if($type==null) {
                throw new Exception\DomainException('Undefined a specified class or instance for parameter:'.$componentName.'::'.$methodName.'( .. $'.$paramName.' .. )');
            }
            if($type==InjectType::ARGUMENT_REFERENCE ||
                $type==InjectType::ARGUMENT_REFERENCE_IN_CONFIG) {
                if($type==InjectType::ARGUMENT_REFERENCE_IN_CONFIG) {
                    $param = ConfigurationFactory::factory($this,null,array('config'=>$param));
                }
                if($param === null)
                    throw new Exception\DomainException('Undefined a specified class or instance for parameter:'.$componentName.'::'.$methodName.'( .. $'.$paramName.' .. )');
                if(is_array($paramSet) && array_key_exists(InjectType::ARGUMENT_DEFAULT, $paramSet)) {
                    if($this->has($param)) {
                        if($this->isDebug)
                            $this->debug('resolve component "'.$param.'" to inject paramater "'.$paramName.'" of "'.$componentName.'".');
                        $param = $this->get($param);
                    } else {
                        $param = $paramSet[InjectType::ARGUMENT_DEFAULT];
                    }
                } else {
                    if($this->isDebug)
                        $this->debug('resolve component "'.$param.'" to inject paramater "'.$paramName.'" of "'.$componentName.'".');
                    $param = $this->get($param);
                }
            } else if($type==InjectType::ARGUMENT_CONFIG) {
                $param = ConfigurationFactory::factory($this,null,array('config'=>$param));
            }
            $params[] = $param;
        }
        return $params;
    }

    protected function parseParam($param,$componentName,$methodName,$paramName)
    {
        if(is_array($param)) {
            // Note: Should consider priorities of arument types.
            //       It is not enough now.
            if(array_key_exists(InjectType::ARGUMENT_VALUE, $param))
                $type = InjectType::ARGUMENT_VALUE;
            else if(array_key_exists(InjectType::ARGUMENT_CONFIG, $param))
                $type = InjectType::ARGUMENT_CONFIG;
            // CAUTION: ARGUMENT_DEFAULT must be on this posion.
            else if(array_key_exists(InjectType::ARGUMENT_DEFAULT, $param))
                $type = InjectType::ARGUMENT_DEFAULT;
            else if(array_key_exists(InjectType::ARGUMENT_REFERENCE, $param))
                $type = InjectType::ARGUMENT_REFERENCE;
            else if(array_key_exists(InjectType::ARGUMENT_REFERENCE_IN_CONFIG, $param))
                $type = InjectType::ARGUMENT_REFERENCE_IN_CONFIG;
            else
                return array(null,null);
            $param = $param[$type];
        } else if(is_string($param)) {
            $type = InjectType::ARGUMENT_REFERENCE;
        } else {
            throw new Exception\DomainException('invalid definition of parameter:'.$componentName.'::'.$methodName.'( .. $'.$paramName.' .. )');
        }
        return array($type,$param);
    }

    protected function injectBySetter($instance,$methodName,$params)
    {
        if($instance instanceof PropertyAccessPolicy) {
            $property = lcfirst(substr($methodName,3));
            $instance->$property = $params[0];
        } else {
            call_user_func_array(array($instance,$methodName), $params);
        }
    }

    protected function newInstanceByParams($className,$params,$componentName)
    {
        if(!class_exists($className)) {
            throw new Exception\DomainException('Undefined class "'.$className.'" in the component "'.$componentName.'"');
        }
        if($this->isDebug)
            $this->debug('create new instance of "'.$componentName.'" from the class "'.$className.'"');
        switch(count($params)) {
            case 0:
                $instance = new $className();
                break;
            case 1:
                $instance = new $className($params[0]);
                break;
            case 2:
                $instance = new $className($params[0],$params[1]);
                break;
            case 3:
                $instance = new $className($params[0],$params[1],$params[2]);
                break;
            case 4:
                $instance = new $className($params[0],$params[1],$params[2],$params[3]);
                break;
            case 5:
                $instance = new $className($params[0],$params[1],$params[2],$params[3],$params[4]);
                break;
            default:
                if (version_compare(PHP_VERSION, '5.1.3') < 0) {
                    throw new Exception\InvalidArgumentException('Too many arguments. Need a version of PHP to upper 5.1.3');
                }
                $ref = new ReflectionClass($className);
                $instance = $ref->newInstanceArgs($params);
                break;
        }
        /* ******
         *
        if($instance instanceof ServiceLocatorAware)
            $instance->setServiceLocator($this);
        */
        return $instance;
    }

    protected function newInstanceByFactory(ComponentDefinition $component,$componentName)
    {
        $factory = $component->getFactory();
        if(!is_callable($factory))
            throw new Exception\DomainException('invalid factory "'.(is_string($factory) ? $factory : 'function').'" in the component "'.($componentName ? $componentName : $component->getName()).'".');
        $factoryArgs = $component->getFactoryArgs();
        if($this->isDebug)
            $this->debug('create new instance of "'.$componentName.'" by factory.');
        $instance = call_user_func($factory,$this,$componentName,$factoryArgs);
        /* ******
        if($instance instanceof ServiceLocatorAware)
            $instance->setServiceLocator($this);
        */
        return $instance;
    }

    public function setInstance($componentName,$instance)
    {
        $this->instanceManager->setInstance($componentName,$instance);
        return $this;
    }
}
